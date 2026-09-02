@extends('layouts.app')

@section('title', 'DIDX — Console Settings')
@section('page-title', 'System Settings & Console Operators')
@section('page-crumb', 'DIDX / System / Settings')

@section('content')
<div class="flex flex-col flex-1 h-full min-h-0 overflow-hidden">
    <!-- 1. TOP TELEMETRY STRIP -->
    <div class="h-9 border-b border-[var(--border)] bg-[var(--surface2)] px-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-[var(--surface)] border border-[var(--border)] font-mono text-xs">
                <i class="fa-solid fa-users text-[10px] text-indigo-500"></i>
                <span class="text-[var(--ink3)] text-[10px]">OPERATORS:</span>
                <span class="font-bold text-[var(--ink1)]">{{ $users->count() }}</span>
            </div>
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-500/10 border border-blue-500/20 font-mono text-xs text-blue-600 dark:text-blue-400">
                <i class="fa-solid fa-shield-halved text-[10px]"></i>
                <span class="text-[10px]">SUPERUSER AUTH ACTIVE</span>
            </div>
        </div>

        <div class="flex items-center gap-2 font-mono text-xs text-[var(--ink3)]">
            <span class="flex items-center gap-1">
                <i class="fa-solid fa-lock text-[10px] text-amber-500"></i>
                <span>Access Restricted: System Admin</span>
            </span>
        </div>
    </div>

    <!-- 2. ACTION BAR (INLINE USER PROVISIONING & SEARCH) -->
    <div class="h-10 px-3 py-1.5 bg-[var(--surface)] border-b border-[var(--border)] flex items-center gap-2 flex-shrink-0 overflow-x-auto">
        <!-- Inline Add User Form -->
        <form method="POST" action="{{ route('settings.add-user') }}" class="flex items-center gap-1.5 m-0 flex-shrink-0">
            @csrf
            <input type="text" name="username" placeholder="Username" required
                   class="h-[26px] px-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-amber-500 w-32">
            <input type="password" name="password" placeholder="Password" required
                   class="h-[26px] px-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-amber-500 w-32">
            <select name="role" class="h-[26px] px-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-amber-500">
                <option value="admin">Admin</option>
                <option value="operator">Operator</option>
            </select>
            <button type="submit" class="btn-dense btn-dense-primary" title="Create Console User">
                <i class="fa-solid fa-user-plus text-[10px]"></i> <span>Add User</span>
            </button>
        </form>

        <div class="h-4 w-[1px] bg-[var(--border)] flex-shrink-0"></div>

        <!-- Filter Search -->
        <div class="relative flex items-center flex-1 max-w-xs">
            <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-2 text-[10px] pointer-events-none"></i>
            <input type="text" id="userSearchInput" placeholder="Filter Username, Role..." oninput="filterUsers()"
                   class="h-[26px] pl-6 pr-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] placeholder-slate-400 focus:outline-none focus:border-amber-500 w-full transition-all">
        </div>

        <div class="flex-1"></div>

        <span class="text-[11px] font-mono text-[var(--ink3)]" id="userCountDisplay">
            {{ $users->count() }} Users
        </span>
    </div>

    <!-- 3. HIGH-DENSITY DATA GRID -->
    <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto relative bg-[var(--surface)]">
        <table class="w-full table-fixed text-xs border-collapse text-left select-text font-mono">
            <colgroup>
                <col class="w-[5%]">
                <col class="w-[35%]">
                <col class="w-[20%]">
                <col class="w-[25%]">
                <col class="w-[15%]">
            </colgroup>
            <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 shadow-xs">
                <tr class="h-8 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">#</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Username</th>
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Assigned Role</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Creation Date</th>
                    <th class="py-2 px-3 text-right">Action Controls</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="usersTbody">
                @forelse($users as $user)
                    <tr class="user-row h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors"
                        data-user="{{ strtolower($user->username) }}"
                        data-role="{{ strtolower($user->role) }}"
                        style="border-left:3px solid {{ $user->role === 'admin' ? 'var(--accent)' : 'var(--teal)' }}">
                        <!-- Serial -->
                        <td class="py-2 px-3 text-center text-[var(--ink3)] border-r border-slate-100 dark:border-slate-800/60">
                            #{{ $user->id }}
                        </td>

                        <!-- Username -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 font-mono tracking-tight font-semibold text-[var(--ink1)] truncate">
                            <div class="flex items-center gap-2 truncate">
                                <div class="w-5 h-5 rounded bg-amber-500/10 text-amber-600 flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                    {{ strtoupper(substr($user->username, 0, 1)) }}
                                </div>
                                <span class="truncate">{{ $user->username }}</span>
                            </div>
                        </td>

                        <!-- Role -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center">
                            <span class="spill {{ $user->role === 'admin' ? 's-route' : 's-pending' }}">
                                <span class="sdot"></span>
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>

                        <!-- Created At -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-[var(--ink3)] text-[11px]">
                            {{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '—' }}
                        </td>

                        <!-- Actions (Anchored Right) -->
                        <td class="py-2 px-3 text-right">
                            <div class="inline-flex items-center justify-end gap-1">
                                <button type="button" onclick="togglePasswordForm({{ $user->id }})" class="btn-dense btn-dense-ghost text-[10px]" title="Update Password">
                                    <i class="fa-solid fa-key text-[9.5px]"></i> Pass
                                </button>
                                <form method="POST" action="{{ route('settings.delete-user', $user->id) }}" class="m-0 inline" onsubmit="return confirm('Delete user {{ $user->username }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-dense btn-dense-del px-1.5" title="Delete User">
                                        <i class="fa-solid fa-trash-can text-[9.5px]"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- Expandable Password Update Row -->
                    <tr id="pw-form-{{ $user->id }}" class="hidden bg-[var(--surface2)] border-b border-[var(--bordersoft)]">
                        <td colspan="5" class="p-2.5">
                            <form method="POST" action="{{ route('settings.update-password', $user->id) }}" class="flex items-center gap-2 m-0 max-w-xl">
                                @csrf
                                <span class="text-[10px] font-mono text-[var(--ink3)] uppercase">Change Password:</span>
                                <input type="password" name="password" placeholder="New Password" required
                                       class="h-7 px-2 bg-[var(--surface)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-amber-500 flex-1">
                                <input type="password" name="password_confirmation" placeholder="Confirm" required
                                       class="h-7 px-2 bg-[var(--surface)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-amber-500 flex-1">
                                <button type="submit" class="btn-dense btn-dense-primary h-7 px-3 text-xs">
                                    <i class="fa-solid fa-floppy-disk text-[10px]"></i> Save
                                </button>
                                <button type="button" onclick="togglePasswordForm({{ $user->id }})" class="btn-dense btn-dense-ghost h-7 px-2 text-xs">
                                    Cancel
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">
                            <i class="fa-solid fa-users text-lg mb-2 block opacity-40"></i>
                            No users registered.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
function togglePasswordForm(id) {
    var el = document.getElementById('pw-form-' + id);
    if (!el) return;
    if (el.classList.contains('hidden')) {
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
}

function filterUsers(){
    var searchVal = (document.getElementById('userSearchInput').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#usersTbody tr.user-row');
    var visibleCount = 0;

    rows.forEach(function(row){
        var u = (row.getAttribute('data-user') || '').toLowerCase();
        var r = (row.getAttribute('data-role') || '').toLowerCase();

        var matches = !searchVal || u.includes(searchVal) || r.includes(searchVal);
        if(matches){
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    var countElem = document.getElementById('userCountDisplay');
    if(countElem) countElem.textContent = visibleCount + ' Users';
}
</script>
@endsection
