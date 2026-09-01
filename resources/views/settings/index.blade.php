@extends('layouts.app')

@section('title', 'DIDX — Console Settings')
@section('page-title', 'Console System Settings & Admin Management')
@section('page-crumb', 'DIDX / System / Settings')

@section('content')
<div class="slabel"><i class="fa-solid fa-gear"></i>Configuration</div>

<div class="card" style="padding:16px 18px;margin-bottom:16px">
    <div class="card-head" style="margin-bottom:12px;padding-bottom:10px">
        <div class="card-title" style="font-size:13px"><i class="fa-solid fa-user-plus"></i>Create Console User</div>
    </div>

    <form method="POST" action="{{ route('settings.add-user') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        @csrf
        <div style="flex:1;min-width:150px">
            <label style="display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;color:var(--ink3);margin-bottom:4px;font-family:var(--mono)">Username</label>
            <input type="text" name="username" placeholder="Username" style="width:100%;padding:6px 10px;border:1px solid var(--border);border-radius:5px;background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:12px;outline:none" required>
        </div>
        <div style="flex:1;min-width:150px">
            <label style="display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;color:var(--ink3);margin-bottom:4px;font-family:var(--mono)">Password</label>
            <input type="password" name="password" placeholder="Password" style="width:100%;padding:6px 10px;border:1px solid var(--border);border-radius:5px;background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:12px;outline:none" required>
        </div>
        <div style="flex:1;min-width:120px">
            <label style="display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;color:var(--ink3);margin-bottom:4px;font-family:var(--mono)">Role</label>
            <select name="role" style="width:100%;padding:6px 10px;border:1px solid var(--border);border-radius:5px;background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:12px;outline:none">
                <option value="admin">Admin</option>
                <option value="operator">Operator</option>
            </select>
        </div>
        <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Add User</button>
    </form>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface)">
        <div class="card-title" style="font-size:13.5px"><i class="fa-solid fa-users"></i>Existing Users</div>
        <div class="cbadge">{{ $users->count() }} users</div>
    </div>

    <div style="overflow-x:auto">
        <table class="table-compact">
            <thead>
                <tr>
                    <th style="width:50px;text-align:center">ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th style="text-align:right;width:140px">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td style="text-align:center;font-family:var(--mono);color:var(--ink3);font-size:11px">#{{ $user->id }}</td>
                        <td style="font-weight:700;color:var(--ink1);font-size:12.5px">{{ $user->username }}</td>
                        <td>
                            <span class="spill s-pending">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td style="font-family:var(--mono);font-size:11px;color:var(--ink3)">{{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '—' }}</td>
                        <td style="text-align:right">
                            <div style="display:inline-flex;align-items:center;gap:4px">
                                <button type="button" onclick="togglePasswordForm({{ $user->id }})" class="btn-sm btn-reset" title="Change Password">
                                    <i class="fa-solid fa-key"></i>
                                </button>
                                <form method="POST" action="{{ route('settings.delete-user', $user->id) }}" style="margin:0;display:inline" onsubmit="return confirm('Delete user {{ $user->username }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm btn-del" title="Delete User">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr id="pw-form-{{ $user->id }}" style="display:none;background:var(--surface2)">
                        <td colspan="5" style="padding:10px 14px">
                            <form method="POST" action="{{ route('settings.update-password', $user->id) }}" style="display:flex;gap:8px;align-items:flex-end;max-width:600px;margin:0">
                                @csrf
                                <div style="flex:1">
                                    <label style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--ink3);margin-bottom:3px;font-family:var(--mono)">New Password</label>
                                    <input type="password" name="password" placeholder="New Password" style="width:100%;padding:5px 8px;border:1px solid var(--border);border-radius:4px;background:var(--surface);color:var(--ink1);font-family:var(--mono);font-size:11.5px;outline:none" required>
                                </div>
                                <div style="flex:1">
                                    <label style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--ink3);margin-bottom:3px;font-family:var(--mono)">Confirm</label>
                                    <input type="password" name="password_confirmation" placeholder="Confirm Password" style="width:100%;padding:5px 8px;border:1px solid var(--border);border-radius:4px;background:var(--surface);color:var(--ink1);font-family:var(--mono);font-size:11.5px;outline:none" required>
                                </div>
                                <button type="submit" class="btn-primary" style="padding:5px 10px;font-size:11px"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                                <button type="button" onclick="togglePasswordForm({{ $user->id }})" class="btn-sm btn-reset">Cancel</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:24px;color:var(--ink3);font-family:var(--mono);font-size:12px">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@section('scripts')
<script>
function togglePasswordForm(id) {
    var el = document.getElementById('pw-form-' + id);
    if (el.style.display === 'none') {
        el.style.display = 'table-row';
    } else {
        el.style.display = 'none';
    }
}
</script>
@endsection
@endsection
