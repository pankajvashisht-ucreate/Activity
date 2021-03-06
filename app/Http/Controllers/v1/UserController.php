<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\v1\ApiController;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\Validator;
use Socialite;
use Illuminate\Support\Facades\Auth;

class UserController extends ApiController
{
 
    private $user;
    public function __construct(UserRepository $user){
        $this->user = $user;
    }

    public function userLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required|string|max:50',
            'social_token' => 'required',
            'social_id' => 'required'
        ]);
        if ($validator->fails()) {
             return $this->error(400,$validator->messages());
        }
        $requestdata=$request->all();
        if(!IsUcreateEmail($requestdata['email'])){
            return $this->error(406,"ucreate employees are authorized to access!!"); 
        }
        $getUser=$this->user->findUserBySocialIdAndEmail($requestdata['social_id'],$requestdata['email']);
        $success=($getUser)?$this->update($getUser):$this->store($requestdata);
        if($success){
            return $this->success($success,"Login Successfully");
        }
        return $this->error(403,"Error to Login");      
    }

    public function store($requestdata){
        $requestdata['authorization_key']=$this->genrateToken();
        if($user=$this->user->create($requestdata)){
            return $this->user->find($user->id);
        }    
        return false;
    }

    public function update($user,$update=[]){
        if(count($update)){
           $data['name'] = $update['name'];    
           $data['social_image'] =$update['social_image'];    
        }
        $user->authorization_key=$this->genrateToken();
        $data['authorization_key']=$user->authorization_key;
        if($this->user->update($user->id,$data)){
            return $user;
        }
        return false;
    }

    public function getUsers($user_id){
        return $this->success($this->user->findNotBookedUser($user_id),'Friends');
    }

    public function gitHub($provider){
        return  Socialite::driver($provider)->redirect();
    }

    public function gitHubUser($provider){
        $requestdata = Socialite::driver($provider)->stateless()->user();
        $token = $requestdata->token;
        $requestdata =  $requestdata->user;
        if (!IsUcreateEmail($requestdata['email'])) {
            flash_message("ucreate employees are authorized to access!!",'d');
            return redirect('/login');
        }
        $getUser = $this->user->findUserBySocialIdAndEmail($requestdata['id'], $requestdata['email']);
        $data = self::SocailFeilds($provider,$requestdata);
        $data['social_token'] = $token;
        $success=($getUser)?$this->update($getUser,$data):$this->store($data);
        if($success){
            Auth::loginUsingId($success->id, true);
            return redirect('/booking');
        }
        flash_message("Error to Login",'d');
        return redirect('/login');
    }

    public function logout(){
        Auth::logout();
        return redirect('/login');
    }

    private static function SocailFeilds(string $provider, array $requestdata){
        if($provider=='github'){
            return [
                'name' => $requestdata['login'],
                'email' => $requestdata['email'],
                'social_id' => $requestdata['id'],
                'social_image' => $requestdata['avatar_url'],
                'user_type' => 0
                ];
        }
        return [
            'name' => $requestdata['name'],
            'email' => $requestdata['email'],
            'social_id' => $requestdata['id'],
            'social_image' => $requestdata['picture'],
            'user_type' => 0
        ];
    }
}
