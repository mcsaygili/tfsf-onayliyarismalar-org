<?php

return [

    'eyebrow' => 'TFSF Approved Competitions',

    'maintenance' => [
        'heading' => 'Under Maintenance',
        'subheading' => 'The jury panel is briefly under maintenance.',
        'card_label' => 'Maintenance Mode',
        'card_title' => "We'll be right back",
        'default_message' => 'We are currently performing scheduled maintenance. Please try again later.',
    ],

    'login' => [
        'heading' => 'Jury Portal',
        'subheading' => 'Log in to manage the competitions assigned to you for evaluation.',
        'card_label' => 'Sign In',
        'card_title' => 'Welcome back',
        'email' => 'Email',
        'email_placeholder' => 'example@tfsf.org.tr',
        'password' => 'Password',
        'remember' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'submit' => 'Log In',
        'no_account' => "Don't have an account yet? Sign up",
    ],

    'register' => [
        'heading' => 'Jury Registration',
        'subheading' => "Register in seconds — you'll fill in your details after signing in.",
        'card_label' => 'Create Account',
        'card_title' => 'Start your registration',
        'email' => 'Email',
        'email_placeholder' => 'example@tfsf.org.tr',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'submit' => 'Sign Up',
        'have_account' => '← Already have an account? Log in',
        'check_email' => 'Your account has been registered. Click the link we emailed you to activate it.',
    ],

    'invitation' => [
        'heading' => 'Jury Invitation',
        'subheading' => 'Complete the jury invitation sent by :institution.',
        'card_label' => 'Secure Registration',
        'card_title' => 'Create your jury account',
        'email' => 'Invitation Email',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'submit' => 'Accept Invitation',
        'accepted' => 'Your jury account has been created and your category assignments have been linked.',
        'account_linked' => 'The invitation has been linked to your existing jury account. You can now log in.',
        'account_unavailable' => 'The existing jury account for this email address is not active. Please contact TFSF.',
        'unnamed_competition' => 'Untitled Competition',
        'mail_subject' => 'Your TFSF jury registration invitation',
        'mail_greeting' => 'Hello :name,',
        'mail_line' => ':institution invited you to serve on the jury for “:competition”.',
        'mail_action' => 'Complete Jury Registration',
        'mail_expiry' => 'This secure link is valid until :date.',
        'mail_ignore' => 'If you do not recognize this invitation, you may ignore this email.',
    ],

    'verify_email' => [
        'heading' => 'Verify your email',
        'subheading' => 'Please verify your email address before continuing.',
        'card_label' => 'Last Step',
        'card_title' => 'Check your inbox',
        'resend' => 'Resend Verification Email',
        'resent' => 'A new verification link has been sent to the email address you provided during registration.',
        'logout' => 'Log Out',
    ],

    'forgot_password' => [
        'heading' => 'Forgot your password?',
        'subheading' => "Enter your email address and we'll send you a link to reset your password.",
        'card_label' => 'Password Reset',
        'card_title' => 'Send a reset link',
        'email' => 'Email',
        'email_placeholder' => 'example@tfsf.org.tr',
        'submit' => 'Send Reset Link',
        'back_to_login' => '← Back to login',
    ],

    'reset_password' => [
        'heading' => 'Set a new password',
        'subheading' => "Choose a strong password you haven't used before.",
        'card_label' => 'Password Reset',
        'card_title' => 'Enter your new password',
        'email' => 'Email',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm Password',
        'submit' => 'Reset Password',
        'back_to_login' => '← Back to login',
    ],

    'account_disabled' => 'Your account is disabled.',

    'nav' => [
        'dashboard' => 'Dashboard',
        'profile' => 'Jury Details',
        'password' => 'Password',
        'logout' => 'Secure Logout',
    ],

    'profile' => [
        'section_title' => 'Jury Details',
        'section_hint' => 'Settings for your own contact details.',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'tckimlikno' => 'National ID Number',
        'save' => 'Save',
        'updated' => 'Your details have been updated.',
    ],

    'password' => [
        'section_title' => 'Password',
        'section_hint' => 'Ensure your account is using a long, random password to stay secure.',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm Password',
        'save' => 'Save',
        'updated' => 'Your password has been updated.',
    ],

    'dashboard' => [
        'incomplete_title' => 'Your details are incomplete',
        'incomplete_text' => 'First name and last name are required.',
        'incomplete_link' => 'Update your details',
    ],

];
