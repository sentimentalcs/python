			

            1.判断混淆 无参数 返回常量 函数
            function zX_() {//(1)
	            function _z() {
	                return '09';//(2)
	            };
	            if (_z() == '09,'){//(3) 
	                return 'zX_';//(4)
	            } else {
	                return _z();
	            }	  //1搜索并整个js把函数替换为空
        	}         //2存入all_var关联数组all_var[(1)()] = (2)==(3)?(4):(2);

        	2.判断混淆 无参数 返回函数 常量
        	function wu_() {//(1)
	            function _w() {
	                return 'wu_'; //(2)
	            };
	            if (_w() == 'wu__') {//(3)
	                return _w();
	            } else {				//1搜索并整个js把函数替换为空
	                return '5%'; //(4)  //2all_var[(1)()] = (2)==(3)?(2):(4);
	            }
        	}

        	3 var参数等于返回值函数
		    var ZA_(1) = function(ZA__){
		            'return ZA_';
		            return ZA__;    //1搜索并整个js把函数替换为空
		     };						//2正则 把 (1)([^\)])把[^\]内容用(1)替换掉

		     4.无参数返回常量
		     var Qh_ = function() {(1)
	            'return Qh_';
	            return ';';(2)
       		 };		//1搜索并整个js把函数替换为空
        	        //2存入all_var关联数组all_var[(1)()] = (2);
        	 
        	 5.无参数返回常量的函数
        	 function ZP_() {(1)
	            'return ZP_';
	            return 'E';(2)
	        }		//1搜索并整个js把函数替换为空
        	        //2存入all_var关联数组all_var[(1)()] = (2);
        	 
        	 6.无参数 返回常量 函数 中间无混淆代码
        	 function do_() {(1)
	            return '';(2)
	         }    //1搜索并整个js把函数替换为空
        	       //2存入all_var关联数组all_var[(1)()] = (2);
        	 
        	 7.闭包无参数
			(function() {
                'return sZ_';
                return '1' //(1)
            })()   //直接搜索并用(1)替换

            8.字符串拼接时使用返回参数的函数
    		(function(iU__) {
                'return iU_';
                return iU__;
            })('9F')//(1)  直接搜索并用(1)替换

		   



