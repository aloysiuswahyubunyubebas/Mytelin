<?php


function filterString($filtername)
{
	if(request('sort') == 'asc'){
		return $filtername.'&sort=desc';
	}
	return $filtername.'&sort=asc';
}


function getImage($image)
{
	if($image){
		$image = json_decode($image);
		return  $image->url ? $image->url : url('/images/default.png');
	}
	return asset('/images/default.png');
}

function currentUrl($number)
{
	$currentpage = request()->segment($number);
	return $currentpage;
}

function userCan($arr)
{	
	if(!is_array($arr)){
		$arr = explode(',',$arr);
	}
	$containsAllValues = !array_diff(auth()->guard(getGuard())->user()->getPermissionNames()->toArray(),$arr);
	return $containsAllValues;
}

function getGuard(){
    if(auth()->guard('web')->check())
        {return "web";}
    elseif(auth()->guard('employee')->check())
        {return "employee";}
}

function checkType()
{
	$type = request('type');
	$arrTypes = ['menu','home','mitra','perseorangan'];
	
	if (is_null($type)) {
		return false;
	}
	if(in_array($type,$arrTypes)){

		return $type;
	}
	return abort(404);
}

function pageTypes()
{
	return ['menu','home','mitra','perseorangan'];
}
function checkRole($parse = false)
{
	$role = request('role');
	if (is_null($role)) {
		return false;
	}
	if ($parse) {
		return ucfirst(str_replace('-', ' ', $role));
	}
	return $role;
}

function typeTitle()
{
	$title = checkType();
	$title = str_replace('_', ' ', $title);
	return ucfirst(str_replace('-', ' ', $title));
}
function get_client_ip()
{
	$ipaddress = '';
	if (isset($_SERVER['HTTP_CLIENT_IP']))
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else if (isset($_SERVER['HTTP_X_FORWARDED']))
		$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
		$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	else if (isset($_SERVER['HTTP_FORWARDED']))
		$ipaddress = $_SERVER['HTTP_FORWARDED'];
	else if (isset($_SERVER['REMOTE_ADDR']))
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	else
		$ipaddress = 'UNKNOWN';
	return $ipaddress;
}

function readCSV($csvFile, $array)
{
	$file_handle = fopen($csvFile, 'r');
	while (!feof($file_handle)) {
		$line_of_text[] = fgetcsv($file_handle, 0, $array['delimiter']);
	}
	fclose($file_handle);
	return $line_of_text;
}


function countDown($date)
{
	$rem = strtotime($date) - time();
	$day = floor($rem / 86400);
	$hr  = floor(($rem % 86400) / 3600);
	$min = floor(($rem % 3600) / 60);
	$sec = ($rem % 60);

	if($day) return  "$day Hari; ". "$hr Jam; ". "$min Menit ";
	if($hr) return "$hr Jam; ". "$min Menit ";
	if($min) return "$min Menit ";

	
}
function encrypt_RSA($plainData, $privatePEMKey, $bit='256')
{
    $encrypted = '';
    $plainData = str_split($plainData, $bit);
    foreach($plainData as $chunk)
    {
      $partialEncrypted = '';

      //using for example OPENSSL_PKCS1_PADDING as padding
      $encryptionOk = openssl_public_encrypt($chunk, $partialEncrypted, $privatePEMKey, OPENSSL_PKCS1_PADDING);

      if($encryptionOk === false){return false;}//also you can return and error. If too big this will be false
      $encrypted .= $partialEncrypted;
    }
    return base64_encode($encrypted);//encoding the whole binary String as MIME base 64
}
      //For decryption we would use:
function decrypt_RSA($publicPEMKey, $data, $bit='256' )
{
		$decrypted = '';
	
		//decode must be done before spliting for getting the binary String
		$data = str_split(base64_decode($data), $bit);
	
		foreach($data as $chunk)
		{
		  $partial = '';
	
		  //be sure to match padding
		  $decryptionOK = openssl_private_decrypt($chunk, $partial, $publicPEMKey, OPENSSL_PKCS1_PADDING);
	
		  if($decryptionOK === false){return false;}//here also processed errors in decryption. If too big this will be false
		  $decrypted .= $partial;
		}
		return $decrypted;
}
function getRSAKeys(){
    $keyPairResource = openssl_pkey_new(array("private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA)); 
    openssl_pkey_export($keyPairResource, $privateKey);
    return [$privateKey, openssl_pkey_get_details($keyPairResource)["key"]];
}