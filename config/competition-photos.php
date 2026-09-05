<?php

return [
    // Raise together with PHP upload_max_filesize/post_max_size and proxy limits.
    'max_file_size_mb' => (int) env('COMPETITION_PHOTO_MAX_MB', 15),
    'max_pixels' => (int) env('COMPETITION_PHOTO_MAX_PIXELS', 100_000_000),
];
