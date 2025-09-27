# Mood Tracker Setup Instructions

## Database Setup

1. **Update the mood_logs table structure** by running the SQL script:
   ```sql
   -- Run this in your phpMyAdmin or MySQL client
   ALTER TABLE `mood_logs` 
   ADD COLUMN `session_id` VARCHAR(255) DEFAULT NULL AFTER `detected_at`,
   ADD COLUMN `duration` INT DEFAULT NULL AFTER `session_id`;
   ```

   Or you can import the provided SQL file: `update_mood_logs_table.sql`

## Features Implemented

### ✅ Real-time Emotion Detection
- The mood tracker now runs continuously without the need to toggle open/close
- Emotions are detected in real-time and displayed in the dashboard
- The camera feed is always visible when the user is logged in

### ✅ Automatic Saving to Database
- When the user clicks the **Done** button, the detected emotion is automatically saved to the `mood_logs` table
- The system saves:
  - `user_id`: Current user's ID
  - `emotion`: The most recent detected emotion
  - `detected_at`: Current timestamp
  - `session_id`: Current PHP session ID
  - `duration`: Total session time in seconds

### ✅ Updated Database Structure
The `mood_logs` table now includes all required columns:
- `id` (Primary Key)
- `user_id` (Foreign Key to users table)
- `emotion` (VARCHAR - detected emotion)
- `detected_at` (TIMESTAMP - when emotion was detected)
- `session_id` (VARCHAR - PHP session identifier)
- `duration` (INT - session duration in seconds)

### ✅ Enhanced Display Pages
- **moodtracker/emotional_analysis.php**: Now shows session duration and session ID
- **admin/admin_mood_logs.php**: Admin view includes all mood log details with session information

### ✅ Continuous Detection
- Removed the open/close toggle functionality
- Mood tracker is always active and visible
- Real-time emotion polling every 2 seconds
- Live indicator shows the system is actively detecting

## How It Works

1. **User logs in**: Camera service starts automatically
2. **Real-time detection**: Emotions are detected and displayed every 2 seconds
3. **Session tracking**: Timer shows current session duration
4. **Save data**: User clicks "Done" to save the session data to database
5. **View results**: Data appears in emotional analysis page and admin logs

## Files Modified/Created

- `dashboard/user_dashboard.php` - Updated mood tracker UI and functionality
- `moodtracker/emotional_analysis.php` - Enhanced to handle POST requests and display session data
- `moodtracker/save_mood.php` - New API endpoint for saving mood data
- `admin/admin_mood_logs.php` - Updated to show session information
- `update_mood_logs_table.sql` - Database update script

## Testing

1. Log in to the user dashboard
2. Verify the mood tracker shows "Live Detection" and is always visible
3. Watch the real-time emotion detection
4. Click "Done" to save the session
5. Check the emotional analysis page to see the saved data
6. Admin users can view all mood logs in the admin panel

The mood tracker now provides continuous, real-time emotion detection with automatic database storage as requested!