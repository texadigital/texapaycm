<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PhoneNumberService
{
    /**
     * Normalize phone number to international format
     * Accepts various formats and converts to E.164 standard
     */
    public static function normalize(string $phoneNumber, string $defaultCountryCode = '237'): string
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Remove leading + if present
        if (str_starts_with($cleaned, '+')) {
            $cleaned = substr($cleaned, 1);
        }
        
        // Remove leading 00 (international dialing prefix)
        if (str_starts_with($cleaned, '00')) {
            $cleaned = substr($cleaned, 2);
        }
        
        // Handle different input formats
        $length = strlen($cleaned);
        
        // If it's already in international format (12 digits starting with 237)
        if ($length === 12 && str_starts_with($cleaned, '237')) {
            return $cleaned;
        }
        
        // If it's 9 digits starting with 6 (Cameroon local format)
        if ($length === 9 && str_starts_with($cleaned, '6')) {
            return $defaultCountryCode . $cleaned;
        }
        
        // If it's 8 digits starting with 6 (Cameroon local format without leading 6)
        if ($length === 8 && str_starts_with($cleaned, '6')) {
            return $defaultCountryCode . '6' . $cleaned;
        }
        
        // If it's 10 digits (might be US format or other country)
        if ($length === 10) {
            // Check if it looks like a Cameroon number
            if (str_starts_with($cleaned, '6')) {
                return $defaultCountryCode . $cleaned;
            }
            // For other countries, assume it needs the default country code
            return $defaultCountryCode . $cleaned;
        }
        
        // If it's 11 digits (might be international without country code)
        if ($length === 11) {
            return $cleaned;
        }
        
        // If it's 13 digits (might have extra leading digit)
        if ($length === 13) {
            // Remove the first digit if it's 0
            if (str_starts_with($cleaned, '0')) {
                return substr($cleaned, 1);
            }
            return $cleaned;
        }
        
        // For any other length, try to add default country code
        if ($length < 12) {
            return $defaultCountryCode . $cleaned;
        }
        
        // Return as-is if we can't determine the format
        return $cleaned;
    }
    
    /**
     * Validate if phone number is in correct format for Cameroon
     */
    public static function validateCameroon(string $phoneNumber): array
    {
        $normalized = self::normalize($phoneNumber);
        
        // Check if it's 12 digits starting with 237
        if (strlen($normalized) !== 12 || !str_starts_with($normalized, '237')) {
            return [
                'valid' => false,
                'error' => 'Phone number must be a valid Cameroon number (e.g., 2376XXXXXXXX)',
                'normalized' => $normalized
            ];
        }
        
        // Check if it's a valid mobile number (starts with 6)
        $mobilePart = substr($normalized, 3, 9);
        if (!str_starts_with($mobilePart, '6')) {
            return [
                'valid' => false,
                'error' => 'Phone number must be a valid Cameroon mobile number',
                'normalized' => $normalized
            ];
        }
        
        // Check provider-specific prefixes
        $prefix3 = substr($normalized, 3, 3);
        $isValidMtn = preg_match('/^(65[0-9]|6[7-8][0-9])/', $prefix3);
        $isValidOrange = preg_match('/^69[0-9]/', $prefix3);
        
        if (!($isValidMtn || $isValidOrange)) {
            return [
                'valid' => false,
                'error' => 'Invalid mobile money number. Please enter a valid MTN or Orange mobile money number.',
                'normalized' => $normalized
            ];
        }
        
        return [
            'valid' => true,
            'normalized' => $normalized,
            'provider' => self::detectProvider($normalized)
        ];
    }
    
    /**
     * Detect mobile money provider based on phone number
     */
    public static function detectProvider(string $normalizedPhone): string
    {
        if (strlen($normalizedPhone) !== 12 || !str_starts_with($normalizedPhone, '237')) {
            return 'UNKNOWN';
        }
        
        $prefix3 = substr($normalizedPhone, 3, 3);
        
        // MTN prefixes
        if (preg_match('/^(65[0-9]|6[7-8][0-9])/', $prefix3)) {
            return 'MTN_MOMO_CMR';
        }
        
        // Orange prefixes
        if (preg_match('/^69[0-9]/', $prefix3)) {
            return 'ORANGE_CMR';
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Format phone number for display
     */
    public static function formatForDisplay(string $phoneNumber): string
    {
        $normalized = self::normalize($phoneNumber);
        
        if (strlen($normalized) === 12 && str_starts_with($normalized, '237')) {
            // Format as +237 6XX XXX XXX
            $mobilePart = substr($normalized, 3);
            return '+237 ' . substr($mobilePart, 0, 3) . ' ' . substr($mobilePart, 3, 3) . ' ' . substr($mobilePart, 6);
        }
        
        return $normalized;
    }
    
    /**
     * Get phone number in E.164 format (with +)
     */
    public static function toE164(string $phoneNumber): string
    {
        $normalized = self::normalize($phoneNumber);
        return '+' . $normalized;
    }
    
    /**
     * Check if two phone numbers are the same (after normalization)
     */
    public static function areSame(string $phone1, string $phone2): bool
    {
        return self::normalize($phone1) === self::normalize($phone2);
    }
    
    /**
     * Extract country code from phone number
     */
    public static function getCountryCode(string $phoneNumber): string
    {
        $normalized = self::normalize($phoneNumber);
        
        if (strlen($normalized) >= 3) {
            return substr($normalized, 0, 3);
        }
        
        return '';
    }
    
    /**
     * Extract local number (without country code)
     */
    public static function getLocalNumber(string $phoneNumber): string
    {
        $normalized = self::normalize($phoneNumber);
        
        if (strlen($normalized) > 3) {
            return substr($normalized, 3);
        }
        
        return $normalized;
    }
}
