<?php
/**
 * (单例版)江西省高校校园防疫自动签到程序
 * User: chuwen
 * Date Time: 2020/7/29 9:49
 * Email: <chenwenzhou@aliyun.com>
 */

//学校代码
//请参考   江西省100所高校.csv  或    江西省100所高校.xlsx
$school_id = "4136010403";
//请输入你的学号，不是两位数的
$sid = "1008611";//学号
//请参考 http://sc.ftqq.com/3.version
$SCKEY = "";//可选的SCKEY

$cookie_jar = __DIR__ . "/cookies/{$sid}.cookie";
file_put_contents($cookie_jar, "");

//执行登录
curlGet("https://fxgl.jx.edu.cn/{$school_id}/public/homeQd?loginName={$sid}&loginType=0", $cookie_jar);

//执行签到
$res = curlPost("https://fxgl.jx.edu.cn/{$school_id}/studentQd/saveStu", $cookie_jar,
    //请注意，如果你要填写 street 参数，必须市根据你输入的经纬度能转换，例如：
    //维度：113.499325
    //经度：24.870886
    //转换成地址就是  江西省赣州市寻乌县东江源大道1号寻乌县人民政府
    //province 参数为 江西省  |  city 参数为 赣州市  |  district 参数为 寻乌县
    //那么 street 参数就应该为  东江源大道1号寻乌县人民政府
    build_sign_data(
    "江西省", "赣州市", "寻乌县", "", 1, 115.64852, 24.95513
));
$de_res = json_decode($res, true);

if(!isset($de_res)){
    die($res);
}

echo $de_res['msg'];//直接输出签到信息

//自己可以去扩展
// 1001 签到成功
// 1002 今日已经签到
if($de_res['code'] === 1001){
    echo "[签到成功]\n";
    $TEXT = "签到成功[1001]";
}else if($de_res['code'] === 1002){
    echo "[今日已经签到]\n";
    $TEXT = "今日已经签过啦[1002]";
}else{
    echo "[UNknown Code]\n";
    $TEXT = "UNknown Code:".$de_res['code'];
}
file_get_contents('https://sc.ftqq.com/'.$SCKEY.'.send?text='.urlencode($TEXT).'&desp='.urlencode($de_res['msg']));

########################################################################################################


/**
 * 构造签到数据
 * @param string $province 省份
 * @param string $city 市
 * @param string $district 区、县
 * @param string $street 街道【H5环境下可选项】
 * @param int $sfby 是否为毕业班的学生   0:是毕业班的学生    1:不是毕业班的学生
 * @param float $lng 经度 180 ~ -180
 * @param float $lat 维度  90 ~ -90
 * @return array
 */
function build_sign_data(
    $province = "江西省", $city = "赣州市",
    $district = "章贡区", $street = "", $sfby = 1,
    $lng = 113.499325, $lat = 24.870886
)
{
    return [
        "province" => $province,//省
        "city" => $city,//市
        "district" => $district,//区/县
        "street" => $street,//街道
        "xszt" => 0,

        "jkzk" => 0,//健康状况  0:健康   1:异常
        "jkzkxq" => "",//异常原因
        "sfgl" => 1,//是否隔离  0:隔离   1:没有隔离

        "gldd" => "",
        "mqtw" => 0,
        "mqtwxq" => "",

        "zddlwz" => $province . $city . $district,//省市县(区) 拼接结果
        "sddlwz" => "",
        "bprovince" => $province,
        "bcity" => $city,
        "bdistrict" => $district,
        "bstreet" => $street,
        "sprovince" => $province,
        "scity" => $city,
        "sdistrict" => $district,

        "lng" => $lng,//经度
        "lat" => $lat,//维度
        "sfby" => $sfby,//是否为毕业班的学生   0:是毕业班的学生    1:不是毕业班的学生
    ];
}


/**
 * curlGET
 * @param string $API           需要请求的API
 * @param string $cookie_jar    jar 文件路径
 * @param array  $header        需要发送的 header
 * @param int    $timout        请求超时时间
 * @return bool|string
 */
function curlGet($API, $cookie_jar, $header = [], $timout = 5)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $API,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => $timout,
        CURLOPT_FOLLOWLOCATION => true,

        CURLOPT_COOKIEJAR => $cookie_jar,
        CURLOPT_COOKIEFILE => $cookie_jar,

        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => $header,
    ));

    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    if ($info['http_code'] !== 200) {
//        print_r($info);
    }

    return $response;
}


/**
 * curlPOST
 * @param string $API           需要请求的API
 * @param string $cookie_jar    jar 文件路径
 * @param array $data           需要发送的POST数据
 * @param array $header         需要发送的 header
 * @param int $timeout          请求超时时间
 * @return bool|string
 */
function curlPost($API = "", $cookie_jar = "", $data = [], $header = [], $timeout = 5)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $API,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEFILE => $cookie_jar,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => $header,
    ));

    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

//    if ($info['http_code'] !== 200) {
//        print_r($info);
//    }

    return $response;
}