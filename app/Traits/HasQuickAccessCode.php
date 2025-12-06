<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasQuickAccessCode
{
    /**
     * Boot the trait and generate code on creation
     */
    protected static function bootHasQuickAccessCode()
    {
        static::creating(function ($model) {
            if (!$model->quick_access_code) {
                $model->quick_access_code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Generate a unique quick access code
     */
    public static function generateUniqueCode(): string
    {
        do {
            // Prefijo según tipo: E=Employee, O=Owner, F=FormControl
            $prefix = match(class_basename(static::class)) {
                'Employee' => 'E',
                'Owner' => 'O',
                'FormControl' => 'F',
                default => 'X'
            };
            
            // Genera código alfanumérico: E-A1B2C3D4
            $randomPart = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8));
            $code = $prefix . '-' . $randomPart;
            
        } while (static::where('quick_access_code', $code)->exists());

        return $code;
    }

    /**
     * Get the URL for quick access (with encrypted code)
     */
    public function getQrCodeUrl(): string
    {
        $encryptedCode = \Illuminate\Support\Facades\Crypt::encryptString($this->quick_access_code);
        return url('/quick-access/' . urlencode($encryptedCode));
    }
    
    /**
     * Get the plain URL for quick access (without encryption)
     */
    public function getPlainQrCodeUrl(): string
    {
        return url('/quick-access/' . $this->quick_access_code);
    }

    /**
     * Generate QR Code as SVG
     */
    public function generateQrCode()
    {
        return \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)
            ->margin(1)
            ->generate($this->getQrCodeUrl());
    }

    /**
     * Regenerate the quick access code
     */
    public function regenerateQuickAccessCode(): bool
    {
        $this->quick_access_code = static::generateUniqueCode();
        return $this->save();
    }
}
