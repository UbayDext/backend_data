<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    private function ok(string $message, $data = null, int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    private function fail(\Throwable $e, int $status = 500)
    {
        return response()->json([
            'success' => false,
            'message' => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server.',
        ], $status);
    }

    private function isAdmin(User $actor): bool
    {
        // aktif kalau tabel users ada kolom role
        return isset($actor->role) && $actor->role === 'admin';
    }

    private function ensureAdminOrSelf(User $actor, User $target): void
    {
        $allowed = $actor->id === $target->id || $this->isAdmin($actor);
        abort_unless($allowed, 403, 'Tidak diizinkan.');
    }

    /* -------------------- INDEX -------------------- */
    // GET /api/users
    public function index(Request $request)
    {
        try {
            $actor = $request->user();

            $q       = trim((string) $request->query('q', ''));
            $perPage = (int) $request->query('per_page', 15);

            $query = User::query()
                ->when($q !== '', function ($builder) use ($q) {
                    $builder->where(function ($x) use ($q) {
                        $x->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                    });
                })
                ->latest();

            if (!$this->isAdmin($actor)) {
                $query->where('id', $actor->id);
            }

            $users = $query->paginate($perPage);
            return $this->ok('Daftar user', $users);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /* -------------------- STORE -------------------- */
    // POST /api/users
  public function store(Request $request)
{
    try {
        $actor = $request->user();
        abort_unless($this->isAdmin($actor), 403, 'Hanya admin yang boleh.');

        $validated = $request->validate([
            'name'       => ['required','string','max:255'],
            'email'      => ['required','string','email','max:255','unique:users,email'],
            'password'   => ['required','string','min:6','confirmed'],
            'role'       => ['required','in:admin,guru_ekskul'],
            'ekskul_id'  => ['nullable','integer','exists:ekskuls,id'],
        ]);

        // Jika role guru_ekskul, ekskul_id wajib
        if ($validated['role'] === 'guru_ekskul' && empty($validated['ekskul_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'ekskul_id wajib untuk role guru ekskul',
            ], 422);
        }

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => $validated['password'],
            'role'      => $validated['role'],
            'ekskul_id' => $validated['ekskul_id'] ?? null,
        ]);

        return $this->ok('User berhasil dibuat', $user, 201);
    } catch (\Throwable $e) {
        return $this->fail($e);
    }
}


    /* --------------------- SHOW -------------------- */
    // GET /api/users/{user}
    public function show(Request $request, User $user)
    {
        try {
            $actor = $request->user();
            $this->ensureAdminOrSelf($actor, $user);

            return $this->ok('Detail user', $user);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /* ------------------- UPDATE -------------------- */
    // PUT/PATCH /api/users/{user}
  public function update(Request $request, User $user)
{
    try {
        $actor = $request->user();
        // Hanya admin boleh ubah user lain; self masih boleh ubah profil dasar
        $this->ensureAdminOrSelf($actor, $user);

        $validated = $request->validate([
            'name'      => ['sometimes','required','string','max:255'],
            'email'     => ['sometimes','required','string','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password'  => ['nullable','string','min:6','confirmed'],
            // Hanya admin boleh ubah role/ekskul_id
            'role'      => [$this->isAdmin($actor) ? 'sometimes' : 'prohibited','in:admin,guru ekskul'],
            'ekskul_id' => [$this->isAdmin($actor) ? 'sometimes' : 'prohibited','nullable','integer','exists:ekskuls,id'],
        ]);

        // aturan tambahan
        if (array_key_exists('role',$validated) && $validated['role'] === 'guru_ekskul') {
            if (!array_key_exists('ekskul_id',$validated) && !$user->ekskul_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ekskul_id wajib untuk role guru ekskul',
                ], 422);
            }
        }

        $payload = [];
        if (array_key_exists('name',$validated))      $payload['name'] = $validated['name'];
        if (array_key_exists('email',$validated))     $payload['email'] = $validated['email'];
        if (!empty($validated['password']))           $payload['password'] = $validated['password'];
        if (array_key_exists('role',$validated))      $payload['role'] = $validated['role'];
        if (array_key_exists('ekskul_id',$validated)) $payload['ekskul_id'] = $validated['ekskul_id'];

        $user->update($payload);

        return $this->ok('User berhasil diupdate', $user->fresh());
    } catch (\Throwable $e) {
        return $this->fail($e);
    }
}


    /* ------------------- DESTROY ------------------- */
    // DELETE /api/users/{user}
    public function destroy(Request $request, User $user)
    {
        try {
            $actor = $request->user();
            abort_unless($this->isAdmin($actor), 403, 'Hanya admin yang boleh.');

            if ($actor->id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak boleh menghapus akun sendiri.',
                ], 422);
            }

            $user->delete();
            return $this->ok('User berhasil dihapus');
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /* ---------------------- ME --------------------- */
    // GET /api/users/me
    public function me(Request $request)
    {
        try {
            return $this->ok('Profil saya', $request->user());
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // PUT /api/users/me
    public function updateMe(Request $request)
    {
        try {
            $me = $request->user();

            $validated = $request->validate([
                'name'  => ['sometimes','required','string','max:255'],
                'email' => ['sometimes','required','string','email','max:255', Rule::unique('users','email')->ignore($me->id)],
            ]);

            $me->update($validated);
            return $this->ok('Profil berhasil diupdate', $me->fresh());
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // PUT /api/users/me/password
  public function changeMyPassword(Request $request)
{
    try {
        $me = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'password'         => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $me->update(['password' => $validated['password']]);
        return $this->ok('Password berhasil diganti');

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors'  => $e->errors(),
        ], 422);
    } catch (\Throwable $e) {
        return $this->fail($e);
    }
}

    // PUT /api/users/{user}/password
    public function changePassword(Request $request, User $user)
    {
        try {
            $actor = $request->user();
            abort_unless($this->isAdmin($actor), 403, 'Hanya admin yang boleh.');

            $validated = $request->validate([
                'password' => ['required','string','min:6','confirmed'],
            ]);

            $user->update(['password' => $validated['password']]); // auto hash
            return $this->ok('Password user berhasil diganti');
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
    private function basicPayload(User $u): array
{
    return [
        'id'       => $u->id,
        'username' => $u->username ?? $u->name, // fallback ke name jika kolom username tidak ada
        'email'    => $u->email,
        'role'     => $u->role ?? null,
    ];
}

public function meBasic(Request $request)
{
    try {
        $me = $request->user();
        return $this->ok('Info dasar user', $this->basicPayload($me));
    } catch (\Throwable $e) {
        return $this->fail($e);
    }
}
}
