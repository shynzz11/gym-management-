<?php

namespace App\Traits;

trait HasProfile
{
    // Placeholder logic for profile related methods common to users
    // For example, getting avatar URL, formatted name, etc.

    public function getProfilePhotoUrl(): string
    {
        // Typically would check for uploaded photo, otherwise return default or gravatar
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name);
    }
}
