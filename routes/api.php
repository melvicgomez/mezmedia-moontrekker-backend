<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BadWeatherController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\ChallengeRewardLogController;
use App\Http\Controllers\ChallengeTrailController;
use App\Http\Controllers\ChallengeTrailImageController;
use App\Http\Controllers\ChallengeUserAttemptController;
use App\Http\Controllers\ChallengeUserAttemptTimingController;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\CorporateController;
use App\Http\Controllers\FCMNotificationController;
use App\Http\Controllers\MoonTrekkerPointsController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\OneTimePinController;
use App\Http\Controllers\ProfileProgressController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLeaderboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('guest')->group(function () {
    Route::post('login', [AuthController::class, 'authenticate'])->name('login')->middleware('throttle:login');
    // OTP Endpoints
    Route::post('request-new-otp', [OneTimePinController::class, 'newOtp'])->middleware('throttle:verify-otp');
    Route::post('verify-requested-otp', [OneTimePinController::class, 'verifyOtp'])->middleware('throttle:request-otp');
    // Route::post('request-new-otp', [OneTimePinController::class, 'newOtp']);
    // Route::post('verify-requested-otp', [OneTimePinController::class, 'verifyOtp']);

    Route::get('announcement/schedule-announcements', [AnnouncementController::class, 'scheduledPosts']);
    Route::get('challenge/schedule-challenges', [ChallengeController::class, 'scheduleChallenge']);
    Route::get('weather-warning/notify', [BadWeatherController::class, 'notifyWarning']);
});

Route::middleware('auth:api')->group(function () {
    Route::post('change-password/{id}', [AuthController::class, 'changePassword']);
    Route::post('create-password', [AuthController::class, 'userCreatePassword']);
    Route::delete('logout', [AuthController::class, 'logout']);
    Route::post('contact-form', [ContactFormController::class, 'contactForm']);

    // User
    Route::resource('users', UserController::class);
    Route::get('user-attempt/{id}', [UserController::class, 'getAllAttemptByUserId']);
    Route::post('register-new-account', [AuthController::class, 'registerNewAccount']);

    // Announcement
    Route::resource('announcement', AnnouncementController::class);
    Route::post('announcement/publish/{id}', [AnnouncementController::class, 'publishPost']);

    // Firebase Cloud Messaging
    Route::get('fcm-token/{token}', [FCMNotificationController::class, 'registerToken']);
    Route::delete('fcm-token/{token}', [FCMNotificationController::class, 'deleteToken']);
    Route::post('message-direct-user', [NotificationsController::class, 'messageDirectToUser']);
    Route::post('message-all-users', [NotificationsController::class, 'messageAllUsers']);

    // MoonTrekker Points
    Route::resource('moontrekker-points', MoonTrekkerPointsController::class);
    Route::get('moontrekker-points-total', [MoonTrekkerPointsController::class, 'retrieveTotalMP']);

    // Team
    Route::resource('team', TeamController::class);
    Route::post('user-team', [TeamController::class, 'joinOrLeaveTeam']);
    Route::resource('corporate', CorporateController::class);

    // Challenge
    Route::resource('challenge', ChallengeController::class);
    Route::get('challenge-list', [ChallengeController::class, 'retrieveChallengeList']);
    Route::post('challenge/publish/{id}', [ChallengeController::class, 'publishChallenge']);
    Route::resource('trail', ChallengeTrailController::class);
    Route::resource('trail-image', ChallengeTrailImageController::class);
    Route::resource('attempt', ChallengeUserAttemptController::class);
    Route::get('challenge-attempt/{id}', [ChallengeUserAttemptController::class, 'getAttemptbyChallengeId']);
    Route::resource('progress', ChallengeUserAttemptTimingController::class);
    Route::resource('reward', ChallengeRewardLogController::class);
    Route::get('challenge-reward', [ChallengeRewardLogController::class, 'getLogForChallenge']);

    // Leaderboard
    // type = solo, duo, team, corporate-team, corporate-cup
    Route::get('leaderboard/moontrekker-points/{type}', [UserLeaderboardController::class, 'leaderboardMoontrekkerPoints']);
    Route::get('leaderboard/race-times/{type}', [UserLeaderboardController::class, 'leaderboardRaceTimes']);
    Route::get('leaderboard/corporate-cup/{id}', [UserLeaderboardController::class, 'leaderboardCorporateCup']);

    // Profile and Progress 
    Route::get('profile/{id}', [ProfileProgressController::class, 'userProfileProgress']);
    Route::get('profile/{id}/{type}', [ProfileProgressController::class, 'userProfileChallengeProgress']);
    Route::get('profile/{id}/{type}', [ProfileProgressController::class, 'userProfileChallengeProgress']);
    Route::get('profile/{id}/{type}', [ProfileProgressController::class, 'userProfileChallengeProgress']);

    // Weather Warning 
    Route::resource('weather-warning', BadWeatherController::class);
});
