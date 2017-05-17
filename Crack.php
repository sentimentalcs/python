<?php



require 'vendor/autoload.php';
use QL\QueryList;


//Crack::test();
class Crack
{
    const CONVERT_TO_UTF8 = true; //是否转换为utf8编码

    /**
     * 测试方法  输出html页面代码
     */
    public static function test()
    {
       $url1 = [
           'http://k.autohome.com.cn/spec/27507/view_1524661_1.html?st=2&piap=1|27507|0|0|1|0|0|0|0|0|1',
           'club.autohome.com.cn/bbs/thread-c-3064-62447072-1.html',
           'http://club.autohome.com.cn/bbs/thread-c-3064-48042133-1.html',
           'club.autohome.com.cn/bbs/thread-c-3064-62447072-1.html',
           'http://club.autohome.com.cn/bbs/thread-c-3064-42346639-1.html',
           'http://club.autohome.com.cn/bbs/thread-c-2990-63126736-1.html',
           'http://club.autohome.com.cn/bbs/thread-c-2990-51373939-1.html#pvareaid=2199101'
       ];

//       $str = file_get_contents('./saved.html');
       $str = self::curl_get($url1[6]);
       $str = self::get_complete_text_autohome($str);
       echo $str;
       exit;
//
//        //验证js
////        $js = file_get_contents('./mix.js');
////        var_dump(self::get_chars($js));
////        $str = self::get_complete_text_autohome($js);
//        //.保存文件
//        // self::save_to_file($str);
////       echo $str;
//        exit;

        $data = QueryList::Query(
           $str,
            [
                'content'=>['.rconten div.tz-paragraph','html']
            ],
            '#maxwrap-maintopic',
            '',
            '',
            true
        )->getData();

        echo '<pre>';
        print_r($data);
        echo '</pre>';
        exit;
    }

    /**
     * @param $text          完整的html代码 从<!DOCTYPE HTML>开始的整个页面的html代码
     * @return mixed|string  返回破解防爬虫后的Html页面代码
     */
    public static function get_complete_text_autohome($text)
    {
        if (self::CONVERT_TO_UTF8) {
            $text = mb_convert_encoding($text, 'UTF-8', 'gbk');
        }
        preg_match("/<!--@HS_ZY@--><script>([\s\S]+)\(document\);<\/script>/", $text, $js);

        if (empty($js[1])) {
            return $text;
        }

        try {
            $char_list = self::get_chars($js[1]);
        } catch (\Exception $e) {
            return $text;
        }
//    var_dump($char_list);

        return  $char_list? preg_replace_callback(
            '/<span\s*class=[\'\"]hs_kw(\d+)_[^\'\"]+[\'\"]><\/span>/',
            function ($matches) use ($char_list) {
                return empty($char_list[$matches[1]])?'':$char_list[(int)$matches[1]];
            },
            $text
        ):$text;

    }

    public static function save_to_file($str)
    {
        $root_path = dirname($_SERVER['SCRIPT_FILENAME']);
        $save_path = $root_path.DIRECTORY_SEPARATOR.'saved_before.html';
        $handle = fopen($save_path,'w');
        if(!empty($str)){
            fwrite($handle,$str);
        }
        fclose($handle);
    }
    /**
     * @param $js        js代码
     * @return array     返回反爬虫的字符串数组
     */
    public static function get_chars($js)
    {
        $all_var = [];

        # 判断混淆 无参数 返回常量 函数
        $if_else_no_args_return_constant_function_functions = [];

        preg_match_all('/function\s+\w+\(\)\s*\{\s*
                function\s+\w+\(\)\s*\{\s*
                    return\s+[\'\"][^\'\"]+[\'\"];\s*
                \};\s*
                if\s*\(\w+\(\)\s*==\s*[\'\"][^\'\"]+[\'\"]\)\s*\{\s*
                    return\s*[\'\"][^\'\"]+[\'\"];\s*
                \}\s*else\s*\{\s*
                    return\s*\w+\(\);\s*
                \}\s*
            \}/x', $js, $l);


        //遍历匹配到的函数
        foreach ($l[0] as $function_k => $function) {
            if (!empty($function)) {
                preg_match('/function\s+(\w+)\(\)\s*\{\s*
                        function\s+\w+\(\)\s*\{\s*
                            return\s+[\'\"]([^\'\"]+)[\'\"];\s*
                        \};\s*
                        if\s*\(\w+\(\)\s*==\s*[\'\"]([^\'\"]+)[\'\"]\)\s*\{\s*
                            return\s*[\'\"]([^\'\"]+)[\'\"];\s*
                        \}\s*else\s*\{\s*
                            return\s*\w+\(\);\s*
                        \}\s*
                    \}/x', $function, $function_name_arr);

                //将函数名和返回值推入数组$if_else_no_args_return_constant_function_functions
                array_push($if_else_no_args_return_constant_function_functions, $function_name_arr);
                // var_dump($function);
                $js = str_replace($function, "", $js);


                list($a, $b, $c, $d) = [self::filter_empty_data($function_name_arr, 1), self::filter_empty_data($function_name_arr, 2), self::filter_empty_data($function_name_arr, 3), self::filter_empty_data($function_name_arr, 4)];
                $all_var[$a.'()'] = $b == $c ? $d : $b;
            }
        }


        //                判断混淆 无参数 返回函数 常量
        $if_else_no_args_return_function_constant_functions = [];
        preg_match_all('/function\s+\w+\(\)\s*\{\s*
                function\s+\w+\(\)\s*\{\s*
                    return\s+[\'\"][^\'\"]+[\'\"];\s*
                \};\s*
                if\s*\(\w+\(\)\s*==\s*[\'\"][^\'\"]+[\'\"]\)\s*\{\s*
                    return\s*\w+\(\);\s*
                \}\s*else\s*\{\s*
                    return\s*[\'\"][^\'\"]+[\'\"];\s*
                \}\s*
            \}/x', $js, $l);


        foreach ($l[0] as $function_k => $function) {
            if (!empty($function)) {
                preg_match('/function\s+(\w+)\(\)\s*\{\s*
                function\s+\w+\(\)\s*\{\s*
                    return\s+[\'\"]([^\'\"]+)[\'\"];\s*
                \};\s*
                if\s*\(\w+\(\)\s*==\s*[\'\"]([^\'\"]+)[\'\"]\)\s*\{\s*
                    return\s*\w+\(\);\s*
                \}\s*else\s*\{\s*
                    return\s*[\'\"]([^\'\"]+)[\'\"];\s*
                \}\s*
            \}/x', $function, $function_name_arr);
                array_push($if_else_no_args_return_function_constant_functions, $function_name_arr);
                $js = str_replace($function, '', $js);
                list($a, $b, $c, $d) = [self::filter_empty_data($function_name_arr, 1), self::filter_empty_data($function_name_arr, 2), self::filter_empty_data($function_name_arr, 3), self::filter_empty_data($function_name_arr, 4)];
                $all_var[$a.'()'] = $b == $c ? $b : $d;
            }
        }

        //这个函数有问题
        //var 参数等于返回值函数
        $var_args_equal_value_functions = [];
        //  var ZA_ = function(ZA__) {
        //     'return ZA_';
        //     return ZA__;
        // };
        preg_match_all('/var\s+[^=]+=\s*function\s*\(\w+\)\s*\{\s*[\'\"]return\s*\w+\s*[\'\"];\s*return\s+\w+;\s*\};/x', $js, $l);
        foreach ($l[0] as $function_k => $function) {
            if (!empty($function)) {
                preg_match('/var\s+([^=]+)/', $function, $function_name_arr);
                $function_name = self::filter_empty_data($function_name_arr, 1);
                array_push($var_args_equal_value_functions, $function_name);
                $js = str_replace($function, '', $js);
                # 替换全文
                $a = trim($function_name);
                $js = preg_replace('/'.$a.'\(([^\)]+)\)/', '\1', $js);

            }
        }

        //        var_dump($all_var);
        //var 无参数 返回常量 函数
        //        var Qh_ = function() {
        //            'return Qh_';
        //            return ';';
        //        };
        $var_no_args_return_constant_functions = [];
        preg_match_all('/var\s+[^=]+=\s*function\s*\(\)\s*\{\s*[\'\"]return\s*\w+\s*[\'\"];\s*return\s+[\'\"][^\'\"]+[\'\"];\s*\};/x', $js, $l);
        foreach ($l[0] as $function_k => $function) {
            if (!empty($function)) {
                preg_match('/var\s+([^=]+)=\s*function\s*\(\)\s*\{\s*[\'\"]return\s*\w+\s*[\'\"];\s*return\s+[\'\"]([^\'\"]+)[\'\"];\s*\};/x', $function, $function_name_arr);
                array_push($var_no_args_return_constant_functions, $function_name_arr);
                $js = str_replace($function, '', $js);
                list($a, $b) = [self::filter_empty_data($function_name_arr, 1), self::filter_empty_data($function_name_arr, 2)];
                $all_var[trim($a).'()'] = $b;
            }
        }


        # 无参数 返回常量 函数
        $no_args_return_constant_functions = [];
        //        function ZP_() {
        //            'return ZP_';
        //            return 'E';
        //        }

        preg_match_all('/function\s*\w+\(\)\s*\{\s*
        [\'\"]return\s*[^\'\"]+[\'\"];\s*
                    return\s*[\'\"][^\'\"]+[\'\"];\s*
                \}\s*/x', $js, $l);

        foreach ($l[0] as $function_k => $function) {
            if (!empty($function)) {
                preg_match('/function\s*(\w+)\(\)\s*\{\s*
                    [\'\"]return\s*[^\'\"]+[\'\"];\s*
                    return\s*[\'\"]([^\'\"]+)[\'\"];\s*
                \}\s*/x', $function, $function_name_arr);
//                var_dump($function_name_arr);
                array_push($no_args_return_constant_functions, $function_name_arr);
                $js = str_replace($function, '', $js);
                list($a, $b) = [self::filter_empty_data($function_name_arr, 1), self::filter_empty_data($function_name_arr, 2)];
                $all_var[trim($a).'()'] = $b;
            }
        }

        # 无参数 返回常量 函数 中间无混淆代码
        $no_args_return_constant_sample_functions = [];
        //    function do_() {
        //            return '';
        //        }
        preg_match_all('/function\s*\w+\(\)\s*\{\s*
                    return\s*[\'\"][^\'\"]*[\'\"];\s*
                \}\s*/x', $js, $l);

        foreach ($l[0] as $function_k => $function) {
            if (!empty($function)) {
                preg_match('/function\s*(\w+)\(\)\s*\{\s*
                    return\s*[\'\"]([^\'\"]*)[\'\"];\s*
                \}\s*/x', $function, $function_name_arr);
                array_push($no_args_return_constant_sample_functions, $function_name_arr);
                $js = str_replace($function, '', $js);
                list($a, $b) = [self::filter_empty_data($function_name_arr, 1), self::filter_empty_data($function_name_arr, 2)];
                $all_var[trim($a).'()'] = $b;
            }
        }

        # 字符串拼接时使无参常量函数
        //    (function() {
        //                'return sZ_';
        //                return '1'
        //            })()
//        preg_match_all('/\(function\(\)\s*\{\s*
//        [\'\"]return[^\'\"]+[\'\"];\s*
//                    return\s*[\'\"][^\'\"]*[\'\"];?
//                \}\)\(\)/x', $js, $l);

        preg_match_all('/\(function\s*\(\)\s*\{\s*[\'\"]return[^\'\"]+[\'\"];\s*return\s*[\'\"][^\'\"]*[\'\"];?\s*\}\)\(\)/x', $js, $l);
        foreach ($l[0] as $function_k => $function) {
            if (!empty($function)) {
                preg_match('/\(function\s*\(\)\s*\{\s*[\'\"]return[^\'\"]+[\'\"];\s*return\s*([\'\"][^\'\"]*[\'\"]);?\s*\}\)\(\)/x', $function, $function_name_arr);
                if (!empty($function_name_arr[1])) {
                    $js = str_replace($function, $function_name_arr[1], $js);
                }

            }
        }


        # 字符串拼接时使用返回参数的函数
        //    (function(iU__) {
        //                'return iU_';
        //                return iU__;
        //            })('9F')
        preg_match_all('/\(function\s*\(\w+\)\s*\{\s*[\'\"]return[^\'\"]+[\'\"];\s*return\s*\w+;\s*}\)\([\'\"][^\'\"]*[\'\"]\)/x', $js, $l);
        foreach ($l[0] as $function_k => $function) {
            if (!empty($function)) {
                preg_match('/\(function\s*\(\w+\)\s*\{\s*[\'\"]return[^\'\"]+[\'\"];\s*return\s*\w+;\s*\}\)\(([\'\"][^\'\"]*[\'\"])\)/x', $function, $function_name_arr);
                $js = str_replace($function, $function_name_arr[1], $js);
            }
        }

        //获取所有变量
//        print_r($js);
//        exit;
//                var_regex = "var\s+(\w+)=(.*?);\s"
        preg_match_all('/var\s+(\w+)=(.*?);\s/', $js, $l, 2);


        if (!empty($l)) {
            foreach ($l as $groups) {
                    $var_value = trim(trim(trim($groups[2]), '\'"'));
                    if (strpos($var_value, '(') !== false) {
                        $var_value = ';';
                    }

                $all_var[$groups[1]] = $var_value;
            }
        }

        # 注释掉 此正则可能会把关键js语句删除掉
        # js = re.sub(var_regex, "", js)
        foreach ($all_var as $var_name => $var_value) {
            $js = str_replace($var_name, $var_value, $js);
        }
//        print_r($js);
        $js = preg_replace("/[\s\+']/", "", $js);
        preg_match('/((?:%\w\w+)+)/',$js,$string_m);

        $_word_list = [];
        if (!empty($string_m[1])) {
            // $string = mb_convert_encoding(urldecode($string_m[1]), 'UTF-8');
           $string = urldecode($string_m[1]);

            //截取后的js字符串
            $substr_js = substr($js, stripos($js, $string_m[1]) + strlen($string_m[1]) - 1);

            preg_match('/([\d,]+(;[\d,]+)+)/', $substr_js, $index_m);

            if (!empty($index_m[1])) {
                $index_list = explode(';', $index_m[1]);

                foreach ($index_list as $word_index_list) {
                    $_word = "";
                    if (strpos($word_index_list, ',')) {
                        $word_index_list_i = explode(',', $word_index_list);
                    } else {
                        $word_index_list_i = [(int)$word_index_list];
                    }

                    foreach ($word_index_list_i as $word_index) {
                        $_word .= mb_substr($string, $word_index, 1, 'utf-8');

                    }
                    array_push($_word_list, $_word);
                }
            }
        }

        return $_word_list;

    }

    /**防止报错
     * @param $data     数组
     * @param $index    索引
     * @return string   存在索引返回该数组索引的值，否则返回空
     */
    private static function filter_empty_data($data, $index)
    {
        return isset($data[$index]) ? $data[$index] : '';
    }

    /**curl  请求url地址，获取html代码的方法
     * @param $url      url地址
     * @return mixed    返回请求页面的html代码
     */
    private static function curl_get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

}

