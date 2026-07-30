<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DidRouteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChannelTestController;
use App\Http\Controllers\LiveCallController;
use App\Http\Controllers\DialerController;
use App\Http\Controllers\SettingsController;

// Redirect root to dashboard
Route::redirect('/', '/dashboard');

// DID Route Management (Dashboard)
Route::get('/dashboard', [DidRouteController::class, 'index'])->name('dashboard');
Route::post('/dashboard/provision', [DidRouteController::class, 'provision'])->name('dashboard.provision');
Route::post('/dashboard/{callLog}/mark-route', [DidRouteController::class, 'markAsRoute'])->name('dashboard.mark-route');
Route::post('/dashboard/{callLog}/reset', [DidRouteController::class, 'resetStatus'])->name('dashboard.reset');
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

