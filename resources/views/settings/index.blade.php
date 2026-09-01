@extends('layouts.app')

@section('title', 'DIDX — Console Settings')
@section('page-title', 'Console System Settings & Admin Management')
@section('page-crumb', 'DIDX / System / Settings')

@section('content')
<div class="slabel"><i class="fa-solid fa-gear"></i>Configuration</div>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-sliders"></i>Create Console User</div>
    </div>

    <form method="POST" action="{{ route('settings.add-user') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:22px">
        @csrf
        <div style="flex:1;min-width:150px">
            <label style="display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;color:var(--ink3);margin-bottom:6px;font-family:var(--mono)">Username</label>
            <input type="text" name="username" placeholder="Username" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:12px;outline:none" required>
        </div>
        <div style="flex:1;min-width:150px">
            <label style="display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;color:var(--ink3);margin-bottom:6px;font-family:var(--mono)">Password</label>
            <input type="password" name="password" placeholder="Password" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:12px;outline:none" required>
        </div>
        <div style="flex:1;min-width:120px">
            <label style="display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;color:var(--ink3);margin-bottom:6px;font-family:var(--mono)">Role</label>
            <select name="role" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:12px;outline:none">
                <option value="admin">Admin</option>
                <option value="operator">Operator</option>
            </select>
        </div>
        <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i>Add User</button>
    </form>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-users"></i>Existing Users</div>
        <div class="cbadge">{{ $users->count() }} users</div>
    </div>

    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;min-width:700px">
            <thead>
                <tr style="border-bottom:1px solid var(--border);color:var(--ink3);font-size:10.5px;text-transform:uppercase;font-family:var(--mono)">
                    <th style="padding:10px 14px">ID</th>
                    <th style="padding:10px 14px">Username</th>
                    <th style="padding:10px 14px">Role</th>
                    <th style="padding:10px 14px">Created At</th>
                    <th style="padding:10px 14px;text-align:center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr style="border-bottom:1px solid var(--bordersoft)">
                        <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink3)">#{{ $user->id }}</td>
                        <td style="padding:12px 14px;font-weight:700">{{ htmlspecialchars($user->username) }}</td>
                        <td style="padding:12px 14px;text-transform:uppercase;font-family:var(--mono);font-size:11px;font-weight:600">{{ $user->role }}</td>
                        <td style="padding:12px 14px;font-family:var(--mono);font-size:11px;color:var(--ink3)">{{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '—' }}</td>
                        <td style="padding:12px 14px;text-align:center">
                            <button type="button" onclick="togglePasswordForm({{ $user->id }})" style="color:var(--primary);background:none;border:none;cursor:pointer;font-weight:700;font-size:12px;margin-right:12px"><i class="fa-solid fa-key"></i> Pass</button>
                            <form method="POST" action="{{ route('settings.delete-user', $user->id) }}" style="margin:0;display:inline" onsubmit="return confirm('Delete user {{ $user->username }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="color:var(--danger);background:none;border:none;cursor:pointer;font-weight:700;font-size:12px"><i class="fa-solid fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    <tr id="pw-form-{{ $user->id }}" style="display:none;background:var(--surface2)">
                        <td colspan="5" style="padding:12px 14px">
                            <form method="POST" action="{{ route('settings.update-password', $user->id) }}" style="display:flex;gap:12px;align-items:flex-end;max-width:700px;margin:0">
                                @csrf
                                <div style="flex:1">
                                    <label style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--ink3);margin-bottom:4px;font-family:var(--mono)">New Password</label>
                                    <input type="password" name="password" placeholder="New Password" style="width:100%;padding:6px 10px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface);color:var(--ink1);font-family:var(--mono);font-size:12px;outline:none" required>
                                </div>
                                <div style="flex:1">
                                    <label style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--ink3);margin-bottom:4px;font-family:var(--mono)">Confirm Password</label>
                                    <input type="password" name="password_confirmation" placeholder="Confirm Password" style="width:100%;padding:6px 10px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface);color:var(--ink1);font-family:var(--mono);font-size:12px;outline:none" required>
                                </div>
                                <button type="submit" class="btn-primary" style="padding:7px 12px;font-size:11px;box-shadow:none"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                                <button type="button" onclick="togglePasswordForm({{ $user->id }})" class="btn-sm btn-reset" style="padding:7px 12px;font-size:11px">Cancel</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr style="border-bottom:1px solid var(--bordersoft)">
                        <td colspan="5" style="text-align:center;padding:24px;color:#9499b3">No users found.</td>
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
