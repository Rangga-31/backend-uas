# reddit Clone - Setup Instructions

## Overview
This is a simple reddit-like web application built with PHP and MySQL. It's designed to be beginner-friendly with clean, well-commented code.

## Features
- User registration and login with secure password hashing
- Create posts (text, image, link, video)
- Comment system with threaded replies
- Upvote/downvote system for posts and comments
- Search functionality across posts and comments
- Responsive design for mobile and desktop

## Requirements
- XAMPP or Laragon (includes Apache, PHP, MySQL)
- Web browser
- Text editor (optional, for code modifications)

## Installation Instructions

### Step 1: Install XAMPP or Laragon
1. Download XAMPP from https://www.apachefriends.org/ OR Laragon from https://laragon.org/
2. Install following the default settings
3. Start Apache and MySQL services

### Step 2: Setup the Project
1. Copy the `reddit-clone` folder to your web server directory:
   - **XAMPP**: Place in `C:\xampp\htdocs\reddit-clone`
   - **Laragon**: Place in `C:\laragon\www\reddit-clone`

### Step 3: Create the Database
1. Open your web browser and go to http://localhost/phpmyadmin
2. Click "New" to create a new database
3. Name it `reddit_clone` and click "Create"
4. Click on the `reddit_clone` database
5. Go to the "SQL" tab
6. Copy and paste the contents of `database.sql` file
7. Click "Go" to execute the SQL commands

### Step 4: Configure Database Connection
1. Open `config.php` in a text editor
2. Update the database settings if needed (default settings work for XAMPP):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'reddit_clone');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Empty for XAMPP, may need password for Laragon
   ```

### Step 5: Create Upload Directory
1. Create a folder named `uploads` inside the `reddit-clone` directory
2. Make sure it has write permissions (usually automatic on Windows)

### Step 6: Access the Application
1. Open your web browser
2. Go to http://localhost/reddit-clone
3. You should see the reddit Clone homepage!

## Default Login Credentials
Two sample users are created automatically:
- **Email**: john@example.com, **Password**: password
- **Email**: jane@example.com, **Password**: password

## File Structure
```
reddit-clone/
├── config.php          # Database connection and helper functions
├── index.php           # Homepage with post listings
├── login.php           # User login page
├── register.php        # User registration page
├── post.php            # Create and view posts
├── search.php          # Search functionality
├── vote.php            # Handle voting on posts/comments
├── logout.php          # User logout
├── database.sql        # Database schema and sample data
├── uploads/            # Directory for uploaded files
└── README.md           # This file
```

## How to Use

### Creating an Account
1. Click "Register" in the header
2. Fill in username, email, and password
3. Click "Register" to create your account

### Creating Posts
1. Log in to your account
2. Click "Create Post" on the homepage
3. Choose post type (Text, Image, Link, or Video)
4. Fill in title and content
5. Upload file or enter URL if needed
6. Click "Create Post"

### Commenting
1. Click on any post title to view the full post
2. Scroll down to the comment section
3. Type your comment and click "Add Comment"
4. Reply to comments by clicking "Reply"

### Voting
1. Use the ▲ and ▼ buttons next to posts and comments
2. Click the same button again to remove your vote
3. Posts are sorted by vote score on the homepage

### Searching
1. Use the search box on the homepage
2. Search terms will match post titles and content
3. Results show both posts and comments

## Troubleshooting

### "Connection failed" Error
- Make sure MySQL is running in XAMPP/Laragon
- Check database name and credentials in `config.php`
- Ensure the `reddit_clone` database exists

### File Upload Issues
- Make sure the `uploads/` directory exists
- Check that PHP has write permissions to the uploads folder
- Verify file types are allowed (jpg, png, gif for images; mp4, webm, ogg for videos)

### Page Not Found
- Ensure you're accessing the correct URL (http://localhost/reddit-clone)
- Make sure Apache is running
- Check that files are in the correct directory

## Security Notes
- This is a learning project and shouldn't be used in production without additional security measures
- All user inputs are sanitized and prepared statements are used for database queries
- Passwords are hashed using PHP's password_hash() function
- File uploads are restricted to specific types and stored outside the web root when possible

## Customization
The code is designed to be easily modified:
- Edit CSS styles in the `<style>` sections of each PHP file
- Modify database schema in `database.sql`
- Add new features by creating additional PHP files
- Customize the appearance by updating the HTML/CSS

## Learning Resources
This project demonstrates:
- PHP basics and best practices
- MySQL database design and queries
- Session management
- File handling and uploads
- Form processing and validation
- Basic web security practices

Feel free to experiment and add your own features!