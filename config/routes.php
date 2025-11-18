<?php

/**
 * Application Routes
 * Format: [method, path, Controller@action]
 */

return [
    // Auth routes
    ['GET', '/login', 'AuthController@showLogin'],
    ['POST', '/login', 'AuthController@login'],
    ['POST', '/logout', 'AuthController@logout'],

    // Dashboard
    ['GET', '/', 'DashboardController@index'],
    ['POST', '/focus', 'DashboardController@saveFocus'],

    // Tasks
    ['GET', '/tasks', 'TaskController@index'],
    ['GET', '/tasks/create', 'TaskController@create'],
    ['POST', '/tasks', 'TaskController@store'],
    ['GET', '/tasks/{id}', 'TaskController@show'],
    ['GET', '/tasks/{id}/edit', 'TaskController@edit'],
    ['POST', '/tasks/{id}', 'TaskController@update'],
    ['POST', '/tasks/{id}/delete', 'TaskController@delete'],
    ['POST', '/tasks/{id}/toggle-status', 'TaskController@toggleStatus'],
    ['POST', '/tasks/{id}/add-hours', 'TaskController@addHours'],

    // Projects
    ['GET', '/projects', 'ProjectController@index'],
    ['POST', '/projects', 'ProjectController@store'],
    ['POST', '/projects/{id}', 'ProjectController@update'],
    ['POST', '/projects/{id}/delete', 'ProjectController@delete'],

    // Time Logs
    ['GET', '/timelogs', 'TimeLogController@index'],
    ['GET', '/timelogs/create', 'TimeLogController@create'],
    ['POST', '/timelogs', 'TimeLogController@store'],
    ['GET', '/timelogs/{id}/edit', 'TimeLogController@edit'],
    ['POST', '/timelogs/{id}', 'TimeLogController@update'],
    ['POST', '/timelogs/{id}/delete', 'TimeLogController@delete'],

    // Deliverables
    ['GET', '/deliverables', 'DeliverableController@index'],
    ['GET', '/deliverables/create', 'DeliverableController@create'],
    ['POST', '/deliverables', 'DeliverableController@store'],
    ['GET', '/deliverables/{id}/edit', 'DeliverableController@edit'],
    ['POST', '/deliverables/{id}', 'DeliverableController@update'],
    ['POST', '/deliverables/{id}/delete', 'DeliverableController@delete'],

    // Notes
    ['GET', '/notes', 'NoteController@index'],
    ['GET', '/notes/create', 'NoteController@create'],
    ['POST', '/notes', 'NoteController@store'],
    ['GET', '/notes/{id}/edit', 'NoteController@edit'],
    ['POST', '/notes/{id}', 'NoteController@update'],
    ['POST', '/notes/{id}/delete', 'NoteController@delete'],

    // Settings
    ['GET', '/settings/lists', 'SettingsController@lists'],
    ['POST', '/settings/lists/add', 'SettingsController@addListItem'],
    ['POST', '/settings/lists/{id}', 'SettingsController@updateListItem'],
    ['POST', '/settings/lists/{id}/delete', 'SettingsController@deleteListItem'],

    // Import
    ['GET', '/import', 'ImportController@index'],
    ['POST', '/import', 'ImportController@import'],

    // AI Features
    ['POST', '/ai/smart-focus', 'AIController@smartFocus'],
    ['POST', '/ai/suggestion-feedback/{id}', 'AIController@suggestionFeedback'],
    ['POST', '/ai/voice-to-task', 'AIController@voiceToTask'],
    ['POST', '/ai/task-breakdown/{id}', 'AIController@taskBreakdown'],
    ['GET', '/ai/pattern-insights', 'AIController@patternInsights'],
    ['GET', '/ai/settings', 'AIController@settings'],
    ['POST', '/ai/settings', 'AIController@updateSettings'],

    // Profile
    ['GET', '/profile', 'ProfileController@index'],
    ['POST', '/profile/update', 'ProfileController@update'],

    // User Management (Admin only)
    ['GET', '/users', 'UserManagementController@index'],
    ['GET', '/users/create', 'UserManagementController@create'],
    ['POST', '/users', 'UserManagementController@store'],
    ['POST', '/users/{id}/delete', 'UserManagementController@delete'],
    ['POST', '/users/{id}/reset-password', 'UserManagementController@resetPassword'],

    // AI Import Center (Admin only) - UPDATED WITH NEW ROUTES
    ['GET', '/ai/import', 'AIImportController@index'],
    ['GET', '/ai/import/oauth-callback', 'AIImportController@oauthCallback'],
    ['POST', '/ai/import/disconnect', 'AIImportController@disconnect'],
    ['POST', '/ai/import/sync', 'AIImportController@sync'],  // Phase 1: Fast sync (RAW data only)
    ['POST', '/ai/import/process-with-ai', 'AIImportController@processWithAI'],  // Phase 2: AI processing (ALL tasks)
    ['POST', '/ai/import/process-selected-with-ai', 'AIImportController@processSelectedWithAI'],  // Phase 2: AI processing (SELECTED tasks)
    ['POST', '/ai/import/sync-with-ai', 'AIImportController@syncWithAI'],  // Legacy: AI mapping
    ['POST', '/ai/import/confirm-ai-import', 'AIImportController@confirmAIImport'],  // Legacy: Confirm AI import
    ['POST', '/ai/import/import', 'AIImportController@import'],
    ['POST', '/ai/import/import-direct', 'AIImportController@importDirect'],  // Direct import without AI
    ['POST', '/ai/import/mapping', 'AIImportController@saveMapping'],
    ['POST', '/ai/import/settings', 'AIImportController@updateSettings'],
];