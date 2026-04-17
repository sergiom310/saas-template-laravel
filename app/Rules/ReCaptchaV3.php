<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ReCaptchaV3 implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $reCaptcha = $value;

        if (trim($reCaptcha)) {
            $secret = "6LdLMzgUAAAAAEs_42BvtJiTWpGOYw0L1UmhsJDa";
            $llamado_get = "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$reCaptcha";
            $response = file_get_contents($llamado_get);
            $responseKeys = json_decode($response, true);
            if(intval($responseKeys["success"]) !== 1) {
                return false;
            }
            return true;
        }        
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Captcha inválido. Prueba que No eres un Robot.!';
    }
}
