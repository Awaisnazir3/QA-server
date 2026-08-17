<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DidRouteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChannelTestController;
use App\Http\Controllers\LiveCallController;
use App\Http\Controllers\DialerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\BulkTestController;

// Redirect root to dashboard
Route::redirect('/', '/dashboard');

// DID Route Management (Dashboard)
Route::get('/dashboard', [DidRouteController::class, 'index'])->name('dashboard');
Route::post('/dashboard/provision', [DidRouteController::class, 'provision'])->name('dashboard.provision');
Route::post('/dashboard/{callLog}/mark-route', [DidRouteController::class, 'markAsRoute'])->name('dashboard.mark-route');
Route::post('/dashboard/{callLog}/reset', [DidRouteController::class, 'resetStatus'])->name('dashboard.reset');
Route::delete('/dashboard/clear-all', [DidRouteController::class, 'clearAll'])->name('dashboard.clear-all');
Route::delete('/dashboard/{callLog}', [DidRouteController::class, 'destroy'])->name('dashboard.destroy');
Route::post('/dashboard/hangup-all', [DidRouteController::class, 'hangupAll'])->name('dashboard.hangup-all');

// Channel Tests
Route::get('/channel-tests', [ChannelTestController::class, 'index'])->name('tests.index');
Route::post('/tests/{callLog}', [ChannelTestController::class, 'test'])->name('tests.test');

// Live Calls
Route::get('/live-calls', [LiveCallController::class, 'index'])->name('calls.live');
Route::post('/live-calls/hangup-all', [LiveCallController::class, 'hangupAll'])->name('calls.hangup-all');
Route::post('/live-calls/hangup', [LiveCallController::class, 'hangupChannel'])->name('calls.hangup-channel');

// Dialer
Route::get('/dialer', [DialerController::class, 'index'])->name('dialer.index');
Route::post('/dialer/make-call', [DialerController::class, 'makeCall'])->name('dialer.make-call');
Route::post('/dialer/hangup-call', [DialerController::class, 'hangupCall'])->name('dialer.hangup-call');
Route::get('/dialer/history', [DialerController::class, 'getHistory'])->name('dialer.history');
Route::post('/dialer/call-status', [DialerController::class, 'updateCallStatus'])->name('dialer.call-status');
Route::post('/dialer/notes', [DialerController::class, 'addNotes'])->name('dialer.notes');
Route::get('/dialer/extension-status', [DialerController::class, 'getExtensionStatus'])->name('dialer.extension-status');

// Reports
Route::get('/reports/cdr', [ReportController::class, 'index'])->name('reports.cdr');
Route::get('/reports/channel-tests', [ReportController::class, 'channelTestReports'])->name('reports.channel-tests');

// Settings
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings/add-user', [SettingsController::class, 'addUser'])->name('settings.add-user');
Route::delete('/settings/users/{user}', [SettingsController::class, 'deleteUser'])->name('settings.delete-user');

// SIP Trunks
Route::get('/sip-trunks', [DidRouteController::class, 'sipTrunks'])->name('sip-trunks');

// API Endpoints for Dashboard Auto-Updates
Route::get('/api/status', [DidRouteController::class, 'apiStatus'])->name('api.status');

// Bulk DID Testing
Route::get('/bulk-test', [BulkTestController::class, 'index'])->name('bulk-test.index');
Route::get('/api/bulk-test/status', [BulkTestController::class, 'apiStatus'])->name('api.bulk-status');
Route::post('/bulk-test/add-single', [BulkTestController::class, 'addSingle'])->name('bulk-test.add-single');
Route::post('/bulk-test/upload', [BulkTestController::class, 'upload'])->name('bulk-test.upload');
Route::post('/bulk-test/reset-all', [BulkTestController::class, 'resetAll'])->name('bulk-test.reset-all');   // static — must be before {bulkDid}
Route::delete('/bulk-test/clear-all', [BulkTestController::class, 'clearAll'])->name('bulk-test.clear-all'); // static — must be before {bulkDid}
Route::get('/bulk-test/export', [BulkTestController::class, 'exportExcel'])->name('bulk-test.export');       // static — must be before {bulkDid}
Route::post('/bulk-test/dial/{bulkDid}', [BulkTestController::class, 'dialSingle'])->name('bulk-test.dial-single');
Route::post('/bulk-test/reset/{bulkDid}', [BulkTestController::class, 'reset'])->name('bulk-test.reset');
Route::delete('/bulk-test/{bulkDid}', [BulkTestController::class, 'destroy'])->name('bulk-test.destroy');


