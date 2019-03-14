<?php

namespace app\api\Controller;

use think\Controller;
use think\facade\Request;
use Config;
use Firebase\JWT\JWT;

/**token类
 * Class Token
 * @package app\api\Controller
 */
class Token extends Controller
{

    /**
     * 创建 token
     * @param array $data 必填 自定义参数数组
     * @param integer $exp_time 必填 token过期时间 单位:秒 例子：7200=2小时
     * @param string $scopes 选填 token标识，请求接口的token
     * @return string
     */
    private $TokenKey = "zhulian";

    public function createToken($data="",$exp_time=0,$scopes=""){

        //JWT标准规定的声明，但不是必须填写的；
        //iss: jwt签发者
        //sub: jwt所面向的用户
        //aud: 接收jwt的一方
        //exp: jwt的过期时间，过期时间必须要大于签发时间
        //nbf: 定义在什么时间之前，某个时间点后才能访问
        //iat: jwt的签发时间
        //jti: jwt的唯一身份标识，主要用来作为一次性token。
        //公用信息
        try {
            $key=$this->TokenKey;
            $time = time(); //当前时间
            //$token['iss']=''; //签发者 可选
            //$token['aud']=''; //接收该JWT的一方，可选
            $token['iat']=$time; //签发时间
            $token['nbf']=$time; //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            if($scopes){
                $token['scopes']=$scopes; //token标识，请求接口的token
            }
            if(!$exp_time){
                $exp_time=7200;//默认=2小时过期
            }
            $token['exp']=$time+$exp_time; //token过期时间,这里设置2个小时
            if($data){
                $token['data']=$data; //自定义参数
            }

            $json = JWT::encode($token,$key);

            $returndata['code']="200";//
            $returndata['msg']='success';
            $returndata['token']= $json;//返回的数据
            return json_encode($returndata); //返回信息


        }catch(\Firebase\JWT\ExpiredException $e){  //签名不正确
            $returndata['code']="104";//101=签名不正确
            $returndata['msg']=$e->getMessage();
            $returndata['data']="";//返回的数据
            return json_encode($returndata); //返回信息
        }catch(\Exception $e) {  //其他错误
            $returndata['code']="199";//199=签名不正确
            $returndata['msg']=$e->getMessage();
            $returndata['data']="";//返回的数据
            return json_encode($returndata); //返回信息
        }
    }

    /**
     * 验证token是否有效,默认验证exp,nbf,iat时间
     * @param string $jwt 需要验证的token
     * @return string $msg 返回消息
     */
    public function checkToken($jwt){
        $key=$this->TokenKey;

        //$jwt = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC93d3cuZm4zMjEuY29tfEZuZWR1Y21zIiwiYXVkIjoiaHR0cDpcL1wvd3d3LmZuMzIxLmNvbXxGbmVkdWNtcyIsImlhdCI6MTU0MTc1NDQ4MiwibmJmIjoxNTQxNzU0NDgyLCJzY29wZXMiOiJyb2xlX2FjY2VzcyIsImV4cCI6MTU0MTc2MTY4MiwiZGF0YSI6eyJ1c2VyaWQiOjIxLCJ1c2VybmFtZSI6Ilx1Njc0ZVx1NWMwZlx1OWY5OSJ9fQ.eSWijLKzIBpS--wPhtL7zUn-ConFA69-FdpfqfDVtpM";
        try {
            JWT::$leeway = 60;//当前时间减去60，把时间留点余地
            $decoded = JWT::decode($jwt, $key, ['HS256']); //HS256方式，这里要和签发的时候对应
            $arr = (array)$decoded;

            $returndata['code']="200";//200=成功
            $returndata['msg']="success";//
            $returndata['data']=$arr;//返回的数据
            return $returndata; //返回信息

        } catch(\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确

            $returndata['code']="101";//101=签名不正确
            $returndata['msg']=$e->getMessage();
            $returndata['data']="";//返回的数据
            //return json_encode($returndata); //返回信息
            exit(json_encode($returndata));
        }catch(\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
            $returndata['code']="102";
            $returndata['msg']=$e->getMessage();
            $returndata['data']="";//返回的数据
            exit(json_encode($returndata)); //返回信息
        }catch(\Firebase\JWT\ExpiredException $e) {  // token过期
            $returndata['code']="103";//103=签名不正确
            $returndata['msg']=$e->getMessage();
            $returndata['data']="";//返回的数据
            exit(json_encode($returndata)); //返回信息
        }catch(\Exception $e) {  //其他错误
            $returndata['code']="199";//199=签名不正确
            $returndata['msg']=$e->getMessage();
            $returndata['data']="";//返回的数据
            exit(json_encode($returndata)); //返回信息
        }
        //Firebase定义了多个 throw new，我们可以捕获多个catch来定义问题，catch加入自己的业务，比如token过期可以用当前Token刷新一个新Token
    }


    public function getToken(){


        header("Access-Control-Allow-Origin: *");
        //自定义信息，不要定义敏感信息
        $data['ip'] = request()->ip();
        $exp_time=7200; //token过期时间,这里设置2个小时
        $scopes='role_access'; //token标识，请求接口的token

        //生成签名
        $json = action('createToken',['data'=>$data,'exp_time'=>$exp_time,'scopes'=>$scopes]);
        return $json;

    }


    public function index1(){


        //生成签名


        if (empty($_SERVER['HTTP_AUTHORIZATION']))
        {
            $res['code'] = 201;
            return json_encode($res);
        }


        $res['code'] = 200;
        $res['token'] = $_SERVER['HTTP_AUTHORIZATION'];
        return json_encode($res);
        //echo $json."<br>"; //返回给客户端token信息
        //验证签名
        /* $checkToken = action('checkToken',['jwt'=>$json]);
         Header("HTTP/1.1 201 Created");
         return $checkToken; //返回给客户端token信息*/


    }





}
