<?php

return [

    'eyebrow' => 'TFSF Approved Competitions',

    'maintenance' => [
        'heading' => 'Under Maintenance',
        'subheading' => 'The institution panel is briefly under maintenance.',
        'card_label' => 'Maintenance Mode',
        'card_title' => "We'll be right back",
        'default_message' => 'We are currently performing scheduled maintenance. Please try again later.',
    ],

    'login' => [
        'heading' => 'Institution Portal',
        'subheading' => 'Log in to organize competitions and manage everything related to the ones you run.',
        'card_label' => 'Sign In',
        'card_title' => 'Welcome back',
        'email' => 'Email',
        'email_placeholder' => 'example@institution.org',
        'password' => 'Password',
        'remember' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'submit' => 'Log In',
        'no_account' => "Haven't registered your institution yet? Sign up",
    ],

    'register' => [
        'heading' => 'Register Your Institution',
        'subheading' => 'Register in seconds — you\'ll fill in institution and staff details after signing in.',
        'card_label' => 'Create Account',
        'card_title' => 'Start your registration',
        'email' => 'Email',
        'email_placeholder' => 'example@institution.org',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'submit' => 'Sign Up',
        'have_account' => '← Already have an account? Log in',
        'check_email' => 'Your institution has been registered. Click the link we emailed you to activate your account.',
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
        'email_placeholder' => 'example@institution.org',
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
        'account' => 'My Account',
        'institution_info' => 'Institution Details',
        'password' => 'Password',
        'staff' => 'Staff',
        'competitions' => 'My Competitions',
        'logout' => 'Secure Logout',
    ],

    'profile' => [
        'institution_section' => 'Institution Details',
        'institution_hint' => 'Information about the institution organizing competitions.',
        'institution_name' => 'Institution Name',
        'institution_email' => 'Institution Email',
        'institution_phone' => 'Institution Phone',
        'institution_website' => 'Website',
        'institution_address' => 'Address',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'phone' => 'Phone',
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

    'staff' => [
        'list_title' => 'Staff',
        'list_hint' => 'People authorized to act on behalf of your institution. You can add more than one.',
        'add_new' => '+ Add New Staff',
        'column_name' => 'Name',
        'column_email' => 'Email',
        'column_phone' => 'Phone',
        'column_status' => 'Status',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'edit_action' => 'Edit',
        'empty' => 'No staff members yet.',
        'pagination_info' => ':first–:last of :total',
        'create_title' => 'Add New Staff',
        'create_hint' => 'Add a new person authorized to act on behalf of your institution.',
        'edit_title' => 'Edit Staff Details',
        'edit_hint' => "Update this staff member's details.",
        'back_to_list' => 'Back to staff list',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'save_new' => 'Add Staff',
        'created' => 'New staff member added.',
        'updated' => 'Staff details updated.',
    ],

    'dashboard' => [
        'incomplete_title' => 'Your institution details are incomplete',
        'incomplete_text' => 'Institution name, email, and phone are required.',
        'incomplete_link' => 'Update your details',
        'total_staff' => 'Total Staff',
    ],

    'field_help' => [
        'open' => 'Show help for :field',
        'close' => 'Close help dialog',
        'example' => 'Example',
    ],

    'competitions' => [
        'list_title' => 'My Competitions',
        'list_hint' => 'Create and track your competition applications here.',
        'add_new' => '+ New Application',
        'complete_profile' => 'Complete Institution Details',
        'incomplete_profile_title' => 'You cannot create a new competition',
        'incomplete_profile_text' => 'The institution name, email, and phone must be completed before creating a competition application.',
        'incomplete_profile_link' => 'Complete institution details.',
        'untitled' => 'Untitled Application',
        'column_name' => 'Competition Name',
        'column_status' => 'Status',
        'column_updated' => 'Last Updated',
        'open_action' => 'Open',
        'empty' => "You don't have any competition applications yet.",
        'pagination_info' => ':first–:last of :total',

        'status' => [
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'needs_info' => 'More Info Needed',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ],

        'steps' => [
            1 => ['label' => 'Competition Details', 'hint' => 'Your competition\'s name, organizing institution, partners, subject, and purpose.'],
            2 => ['label' => 'Step 2'],
            3 => ['label' => 'Step 3'],
            4 => ['label' => 'Step 4'],
            5 => ['label' => 'Step 5'],
            6 => ['label' => 'Step 6'],
            7 => ['label' => 'Step 7'],
            8 => ['label' => 'Step 8'],
            9 => ['label' => 'Step 9'],
            10 => ['label' => 'Step 10'],
        ],

        'fields' => [
            'name' => 'Competition Name',
            'organizing_institution' => 'Organizing Institution',
            'organizing_institution_hint' => 'The institution you are signed in as is used automatically and cannot be changed here.',
            'partners' => 'Partners and Collaborators',
            'partners_placeholder' => 'E.g. Institution A, Institution B, Institution C',
            'partners_hint' => 'Optional. Separate multiple partners or collaborators with commas (,).',
            'subject' => 'Competition Subject',
            'purpose' => 'Competition Purpose',
            'characters_remaining' => ':remaining characters remaining (maximum :max).',
        ],

        'field_help' => [
            'name' => [
                'description' => 'Enter the clear, official competition name that will appear in announcements, regulations, and results.',
                'example' => 'TFSF 2026 National Nature Photography Competition',
            ],
            'organizing_institution' => [
                'description' => 'The organizing institution is taken automatically from the institution account you are signed in with and cannot be changed on this screen.',
            ],
            'partners' => [
                'description' => 'You may enter partners and collaborators contributing to the competition. This field is optional; separate multiple institutions with commas.',
                'example' => 'Example Municipality, Example Photography Association, Example University',
            ],
            'subject' => [
                'description' => 'Describe the competition theme or subject clearly. This field is required and may contain up to 1000 characters.',
                'example' => 'Photographs documenting Türkiye’s wildlife, biodiversity, and protected natural areas.',
            ],
            'purpose' => [
                'description' => 'Explain the aim, objectives, and intended impact of the competition. This field is required and may contain up to 1000 characters.',
                'example' => 'To raise public awareness of wildlife conservation and support the art of photography.',
            ],
        ],

        'save_draft' => 'Save as Draft',
        'next_step' => 'Next',
        'draft_saved' => 'Draft saved.',
        'coming_soon' => 'This step will be added soon.',
        'needs_info_title' => 'More information requested',
        'ready_to_submit_title' => 'Ready to submit',
        'ready_to_submit_hint' => 'What you\'ve entered will be sent to EYS for approval.',
        'submit_for_approval' => 'Submit for Approval',
        'submitted' => 'Your application has been submitted for approval.',
        'cannot_submit_incomplete' => 'You need to complete the required fields before submitting.',
    ],

];
