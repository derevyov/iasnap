<?php

/**
 * RegForm class.
 * RegForm is the data structure for keeping
 * user register form base data. It is used by the 'register' action of 'AuthController'.
 */
class RegForm extends CFormModel
{

	public $Signature;
	public $CertSign;
	public $CertCypher;
	public $Email;
	public $Email2;
	public $Phone;
	public $ConfirmPersonalData;
	public $SigData;
	public $Acceptance;
	public $AgreementText;
	public $CertExpireBeginTime;
	public $CertExpireEndTime;
	public $TypeOfUser = 0; //0 Fiz osoba, 2 Ur osoba
	public $CertSignOrg;
	public $CertCypherOrg;
	public $CertOrgExpireBeginTime;
	public $CertOrgExpireEndTime;
	public $SigDataOrg;

	private $_identity;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
	        Yii::import('ext.MyValidators.stringIsBase64');
		return array(
			array('Signature, Email, Email2, Phone', 'required'),
			array('Signature', 'stringIsBase64'),
//			array('Signature', 'authenticate'),
			array('Email, Email2', 'email'),
			array('Email, Email2', 'length', 'max'=>45),
       		array('Email, Email2', 'length', 'min'=>6, 'max'=>50),
			array('Email2', 'compare', 'compareAttribute'=>'Email'),
			array('Phone, TypeOfUser', 'numerical', 'integerOnly'=>true),
			array('Acceptance', 'required', 'message'=>'Ви повинні надати згоду на обробку персональних даних щоб подавати заявки в електронному вигляді (необхідно поставити позначку)'),
        		// Почта должна быть уникальной
//        		array('Email', 'unique'),
        		// Почта должна быть написана в нижнем регистре
//        		array('Email', 'filter', 'filter'=>'mb_strtolower'),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array(
			'Email'=>'Електронна пошта',
			'Email2'=>'Підтвердження електронної пошти',
			'Phone'=>'Номер телефону',
			'Acceptance'=>'З метою забезпечення обробки поданих мною в електронному вигляді заяв на отримання адміністративних послуг і відповідно до Закону України "Про захист персональних даних" я даю згоду на оброблення моїх персональних даних з використанням порталу Центра надання адміністративних послуг.',
//			'rememberMe'=>'Remember me next time',
		);
	}

	/**
	 * Logs in the user using the given username and password in the model.
	 * @return boolean whether login is successful
	 */
	public function verify_sign()
	{
error_log("RegForm,verify_sign");
		$this->SigData = new EUSignature($this->Signature);
		$er = $this->SigData->check();
error_log("RegForm,SignError:".$this->SigData->iErrorCode);
		if($this->SigData->iErrorCode!=0)
		{
			$this->addError('Signature', 'Помилка при перевірці ЕЦП');
			return false;
		}
		$SignedData = explode(";", $this->SigData->sResultData);
error_log("RegForm,sResultData:".$this->SigData->sResultData);
//	for fiz.osoba must be:	[Email];[Email2];[Phone];[AgreementText];[randstr];[CertSign];[CertExpireEndTime];[CertExpireBeginTime]
//	for ur.osoba must be:  {[Email];[Email2];[Phone];[AgreementText];[randstr];[CertSign];[CertEndTime];[CertBeginTime]};[randstr];[CertSignOrg];[CertOrgExpireEndTime];[CertOrgExpireBeginTime]
// --- Begin Ur osoba sign checking ---
		if ($this->TypeOfUser == 2) {	// Ur osoba
error_log("RegForm,Ur osoba");
			$this->SigDataOrg = $this->SigData;
			$this->SigData = "";
			if(sizeof($SignedData)<5) {
				$this->addError('Acceptance', 'Отримані пошкоджені дані. Процедуру реєстрації необхідно розпочати знову. Перейдіть на сторінку реєстрації за посиланням у правому верхньому куті екрану.');
				return false;
			}
			$randstr = $SignedData[1];
//	Check if random string is not outdated
			if (strlen($randstr) != 40) {
				error_log("RegForm: Error: randstr has no valid auth string length");
				$this->addError('Acceptance', 'Отримані пошкоджені дані. Процедуру реєстрації необхідно розпочати знову. Перейдіть на сторінку реєстрації за посиланням у правому верхньому куті екрану.');
				return false; }
			if (preg_match('/[^a-zA-Z0-9\.\$\[\]\!@\*\+\-\{\}]/s', $randstr) > 0) {
				error_log("RegForm: Error: Auth string has incorrect symbols");
				$this->addError('Acceptance', 'Процедура реєстрації виконується некоректно. Необхідно перейти на сторінку реєстрації знову, заповнити поля електронної пошти, телефону, підтвердити згоду та натиснути кнопку "Реєстрація".');
				return false; }
			GenStr::model()->deleteAll("itime < :itime", array('itime' => time()-120));
			if (GenStr::model()->count('sauth=:sauth', array(':sauth'=>$randstr)) <= 0) {
				error_log("RegForm: Error: Auth string has expired 120s");
				$this->addError('Acceptance', 'Процедура реєстрації зайняла надто великий час. Необхідно розпочати знову.');
				return false; }
//	GenStr::model()->findByAttributes('sauth=:sauth', array(':sauth'=>$randstr))->delete();
			GenStr::model()->find('sauth=:sauth', array(':sauth'=>$randstr))->delete();
			$this->CertSignOrg = $SignedData[2];
			if (isset($SignedData[3]) && isset($SignedData[4])) {
//			try {
				$date1 = DateTime::createFromFormat('D M d H:i:s T Y', $SignedData[3]);
				$date2 = DateTime::createFromFormat('D M d H:i:s T Y', $SignedData[4]);
				$this->CertOrgExpireEndTime = $date1->getTimestamp(); //!! - we start with EndTime, because in such order it is put to signature
				$this->CertOrgExpireBeginTime = $date2->getTimestamp();
//			} catch(e) {$this->CertExpireEndTime = "";};
			} else {$this->CertOrgExpireBeginTime = ""; $this->CertOrgExpireEndTime = "";}
			
			$this->SigData = new EUSignature($SignedData[0]);
			$er = $this->SigData->check();
error_log("RegForm,Ur,SignError:".$this->SigData->iErrorCode);
			if($this->SigData->iErrorCode!=0)
			{
				$this->addError('Signature', 'Помилка при перевірці ЕЦП керівника');
				return false;
			}
			$SignedData = explode(";", $this->SigData->sResultData);
			
		}

// --- End Ur osoba sign checking ---
		
		if(sizeof($SignedData)<6) {	// Some data missing...
									// 0-Email, entered by user, 1-Email, repeated by user, 2-Phone, entered by user,
									// 3-Personal data agreement text, signed by user (whole array signed, so we need to store whole array)
									// 4-Random string, generated during running of sign java application. It must exist in DB, and not be expired by 120 seconds
									// 5-Certificate, obtained from user side
									// 6-Certificate expiration date, obtained from user side, may be missing
			$this->addError('Acceptance', 'Отримані пошкоджені дані. Процедуру реєстрації необхідно розпочати знову. Перейдіть на сторінку реєстрації за посиланням у правому верхньому куті екрану.');
			return false;
		}
		$this->Email = $SignedData[0];
		$this->Email2 = $SignedData[1];
		$this->Phone = $SignedData[2];
		$this->AgreementText = $SignedData[3];
		$randstr = $SignedData[4];
		$this->CertSign = $SignedData[5];
		if (isset($SignedData[6]) && isset($SignedData[7])) {
//			try {
				$date1 = DateTime::createFromFormat('D M d H:i:s T Y', $SignedData[6]);
				$date2 = DateTime::createFromFormat('D M d H:i:s T Y', $SignedData[7]);
				$this->CertExpireEndTime = $date1->getTimestamp(); //!! - we start with EndTime, because in such order it is put to signature
				$this->CertExpireBeginTime = $date2->getTimestamp();
//			} catch(e) {$this->CertExpireEndTime = "";};
		} else {$this->CertExpireBeginTime = ""; $this->CertExpireEndTime = "";}
error_log($this->AgreementText);
//	Check email unique for external users
//		$unique_user = CabUser::model()->findByAttributes(array('email'=>$this->Email, 'user_roles_id'=>'4'));
// Don't allow to register external users with same email at all, even if existing email is belong to internal user
		$unique_user = CabUser::model()->findByAttributes(array('email'=>$this->Email));
		if (!($unique_user===null)){	// This email already registered
			$this->addError('Email', 'Користувач з таким e-mail вже зареєстрований. Якщо ви реєструвались раніше, спробуйте увійти у особистий кабінет з використанням власного ЕЦП.');
			return false;
		}
// Don't allow to register external users with same phone at all, even if existing phone is belong to internal user
		$unique_user = CabUser::model()->findByAttributes(array('phone'=>$this->Phone));
		if (!($unique_user===null)){	// This email already registered
			$this->addError('Phone', 'Користувач з таким номером телефону вже зареєстрований. Якщо ви реєструвались раніше, спробуйте увійти у особистий кабінет з використанням власного ЕЦП.');
			return false;
		}
//	Check if random string is not outdated
	if (strlen($randstr) != 40) {
		error_log("RegForm: Error: randstr has no valid auth string length");
		$this->addError('Acceptance', 'Отримані пошкоджені дані. Процедуру реєстрації необхідно розпочати знову. Перейдіть на сторінку реєстрації за посиланням у правому верхньому куті екрану.');
		return false; }
	if (preg_match('/[^a-zA-Z0-9\.\$\[\]\!@\*\+\-\{\}]/s', $randstr) > 0) {
		error_log("RegForm: Error: Auth string has incorrect symbols");
		$this->addError('Acceptance', 'Процедура реєстрації виконується некоректно. Необхідно перейти на сторінку реєстрації знову, заповнити поля електронної пошти, телефону, підтвердити згоду та натиснути кнопку "Реєстрація".');
		return false; }
	GenStr::model()->deleteAll("itime < :itime", array('itime' => time()-120));
	if (GenStr::model()->count('sauth=:sauth', array(':sauth'=>$randstr)) <= 0) {
		error_log("RegForm: Error: Auth string has expired 120s");
		$this->addError('Acceptance', 'Процедура реєстрації зайняла надто великий час. Необхідно розпочати знову.');
		return false; }
//	GenStr::model()->findByAttributes('sauth=:sauth', array(':sauth'=>$randstr))->delete();
	GenStr::model()->find('sauth=:sauth', array(':sauth'=>$randstr))->delete();
					
error_log("RegForm,SignVer:".$this->SigData->sIssuer);
return true;

//		if($this->_identity->errorCode===EUUserIdentity::ERROR_NONE)
//		{
//			$duration=0; //$this->rememberMe ? 3600*24*30 : 0; // 30 days
//			Yii::app()->user->login($this->_identity,$duration);
//			return true;
//		}
//		else
//			return false;
	}
}
