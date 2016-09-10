<?php

/**
* 
*/
class Validations
{
	
	function __construct()
	{
		# code...
	}

	public function validatePhoneNumber($phoneNumber){
		$regex = "/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i";
		if(preg_match( $regex, $phoneNumber)){
			return TRUE;
		}else{
			return FALSE;
		}
	}

	public function validateURL($url){
		if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
		    return FALSE;
		}
		return TRUE;
	}

	public function validateDate($date)
	{
	    $d = DateTime::createFromFormat('Y-m-d', $date);
	    return $d && $d->format('Y-m-d') == $date;
	}

	public function verify_time_format($value) {
	  $pattern1 = '/^(0?\d|1\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';
	  $pattern2 = '/^(0?\d|1[0-2]):[0-5]\d\s(am|pm)$/i';
	  return preg_match($pattern1, $value) || preg_match($pattern2, $value);
    }

    public function validateDateTime($date, $format = 'Y-m-d H:i:s')
	{
	    $d = DateTime::createFromFormat($format, $date);
	    return $d && $d->format($format) == $date;
	}

	public function isValidName($value){
		$regexp = "/^[a-zA-Z_\s áéíóúüñÁÉÍÓÚÜÑ]{1,}$/iu"; 
		if(is_string($value) && preg_match($regexp, $value)){                                                                                        
            return TRUE;
        }
        return FALSE;
	}

	public function validateEmail($email) {
    
	    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	        return FALSE;
	    }
	    return TRUE;
	}
}
?>