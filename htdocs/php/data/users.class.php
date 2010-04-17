<?php
/* copyright ryan day 2010 */
class doUsers{
	public function get($args){
		$users_id = get($args,'users_id');
		if($users_id) {
			if($user = DBTable::get('users')->loadRow($users_id)){
				return data::success($user->export());
			}
			return data::error('invalid user id');
		}
		return data::error('user id required');
	}

	public function create($args){
		extract(extractable(array('email','name','password'),$args));
		if($email && $password){
			$email = strtolower(trim($email));
			if(validateEmail($email)){
				$ut = DBTable::get('users');
				if(!$rows = $ut->loadRowsWhere(array('email'=>$email))){
					$new = $ut->loadNewRow();
					$new->name = $name;
					$new->email = $email;
					$new->password = sha1($password);
					$new->created = time();
					$new->save();
					if($new->users_id) {
						return data::success($new->export());
					}
					return data::error("Unknown error saving user. Please try again.");
				}
				return data::error("email is already registered");
			}
			return data::error("invalid email");
		}
		return data::error("email and password required");
	}

	public function delete($args){
		$users_id = get($args,'users_id');
		if($users_id) {
			if($user = DBTable::get('users')->loadRow($users_id)){
				DBTable::deleteRow($user);
				return data::success($user->export());
			}
			return data::error('invalid user id');
		}
		return data::error('user id required');
	}
}
?> 
