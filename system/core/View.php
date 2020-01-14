<?php

namespace core;
class View
{
    private static $templateFile;
    public static $_var = array();
    public static $_foreachmark = '';
    public static $_foreach = array();
    public static $_temp_key = array(); // 临时存放 foreach 里 key 的数组
    public static $_temp_val = array(); // 临时存放 foreach 里 item 的数组
    public static $_patchstack = '';
    public static $_nowtime = null;

    function __construct()
    {
        self::$_nowtime = time();
        header('Content-type: text/html; charset=utf-8');
    }


    /**
     * 展示页面
     * @param $viewFile
     * @param $data
     */
    public static function display($viewFile, $data)
    {
        //赋值给变量self::$_var
        if (is_array($data)) {
            foreach ($data as $datakey => $dataValue) {
                if ($dataValue != '') {
                    self::assign($datakey, $dataValue);
                }
            }
        }

        $content = self::fetch($viewFile);

        echo $content;
    }

    private static function fetch($viewFile)
    {

        $filename = self::checkTemplate($viewFile);
        $content = self::makeCompiled($filename);
        return $content;
    }

    /**
     * 编译模板函数
     * @access  public
     * @param   string $filename
     * @return  sring        编译后文件地址
     */
    private static function makeCompiled($filename)
    {
        $source = '';
        if (file_exists($filename)) {
            $source = self::fetchStr(@file_get_contents($filename));

            $source = self::_eval($source);
            //$source = self::_require($source);
        }
        return $source;
    }

    private static function _eval($content)
    {
        ob_start();
        eval('?' . '>' . trim($content));
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * 处理字符串函数
     * @access  public
     * @param   string $source
     * @return  sring
     */
    private static function fetchStr($source)
    {
        //return preg_replace("/{([^\}\{\n]*)}/e", "\self::select('\\1');", $source);
        return preg_replace_callback("/{([^\}\{\n]*)}/", function ($r) {
            return self::select($r[1]);
        }, $source);
    }

    /**
     * 处理{}标签
     * @access  public
     * @param   string $tag
     * @return  sring
     */
    private static function select($tag)
    {
        $tag = stripslashes(trim($tag));

        if (empty($tag)) {
            return '{}';
        } elseif ($tag{0} == '*' && substr($tag, -1) == '*') {
            // 注释部分
            return '';
        } elseif ($tag{0} == '$') {
            // 变量
            return '<?php echo ' . self::get_val(substr($tag, 1)) . '; ?>';
        } elseif ($tag{0} == '/') {
            // 结束 tag
            switch (substr($tag, 1)) {
                case 'if':
                    return '<?php endif; ?>';
                    break;

                case 'foreach':
                    if (self::$_foreachmark == 'foreachelse') {
                        $output = '<?php endif; unset($_from); ?>';
                    } else {
                        array_pop(self::$_patchstack);
                        $output = '<?php endforeach; endif; unset($_from); ?>';
                    }
                    $output .= "<?php self::pop_vars(); ?>";

                    return $output;
                    break;

                case 'literal':
                    return '';
                    break;

                default:
                    return '{' . $tag . '}';
                    break;
            }
        } else {
            $tag_array = explode(' ', $tag);
            $tag_sel = array_shift($tag_array);
            switch ($tag_sel) {
                case 'if':
                    return self::_compileIfTag(substr($tag, 3));
                    break;

                case 'else':
                    return '<?php else: ?>';
                    break;

                case 'elseif':
                    return self::_compileIfTag(substr($tag, 7), true);
                    break;

                case 'foreachelse':
                    self::$_foreachmark = 'foreachelse';
                    return '<?php endforeach; else: ?>';
                    break;

                case 'foreach':
                    self::$_foreachmark = 'foreach';
                    if (!isset(self::$_patchstack)) {
                        self::$_patchstack = array();
                    }
                    return self::_compileForeachStart(substr($tag, 8));
                    break;

                case 'assign':
                    $t = self::getPara(substr($tag, 7), 0);

                    if ($t['value']{0} == '$') {
                        /* 如果传进来的值是变量，就不用用引号 */
                        $tmp = 'self::assign(\'' . $t['var'] . '\',' . $t['value'] . ');';
                    } else {
                        $tmp = 'self::assign(\'' . $t['var'] . '\',\'' . addcslashes($t['value'], "'") . '\');';
                    }

                    return '<?php ' . $tmp . ' ?>';
                    break;

                case 'include':
                    $t = self::getPara(substr($tag, 8), 0);

                    if (substr($t[file], -4, 4) != 'html') {
                        $code = var_export(self::$_var[$t['inc_var']], 1);
                        if ($t['inc_var']) {
                            return '<?php self::assign(\'inc_var\',' . $code . ');echo self::fetch(' . "$t[file]" . '); ?>';
                        } else {
                            return '<?php echo self::fetch(' . "$t[file]" . '); ?>';
                        }

                    } else {
                        $code = var_export(self::$_var[$t['inc_var']], 1);
                        if ($t['inc_var'])
                            return '<?php self::assign(\'inc_var\',' . $code . ');echo self::fetch(' . "'$t[file]'" . '); ?>';
                        else
                            return '<?php echo self::fetch(' . "'$t[file]'" . '); ?>';
                    }


                    break;

                case 'insert_scripts':
                    $t = self::getPara(substr($tag, 15), 0);

                    return '<?php echo self::smarty_insert_scripts(' . self::makeArray($t) . '); ?>';
                    break;

                case 'create_pages':
                    $t = self::getPara(substr($tag, 13), 0);

                    return '<?php echo self::smarty_create_pages(' . self::makeArray($t) . '); ?>';
                    break;
                case 'insert' :
                    $t = self::getPara(substr($tag, 7), false);
                    $out = "<?php \n" . '$k = ' . preg_replace("/(\'\\$[^,]+)/e", "stripslashes(trim('\\1','\''));", var_export($t, true)) . ";\n";
                    $out .= 'echo $k[\'name\'] . \'|\' . base64_encode(serialize($k));' . "\n?>";

                    return $out;
                    break;

                case 'literal':
                    return '';
                    break;

                case 'cycle' :
                    $t = self::getPara(substr($tag, 6), 0);

                    return '<?php echo self::cycle(' . self::makeArray($t) . '); ?>';
                    break;

                case 'html_options':
                    $t = self::getPara(substr($tag, 13), 0);

                    return '<?php echo self::html_options(' . self::makeArray($t) . '); ?>';
                    break;

                case 'html_select_date':
                    $t = self::getPara(substr($tag, 17), 0);

                    return '<?php echo self::html_select_date(' . self::makeArray($t) . '); ?>';
                    break;

                case 'html_radios':
                    $t = self::getPara(substr($tag, 12), 0);

                    return '<?php echo self::html_radios(' . self::makeArray($t) . '); ?>';
                    break;

                case 'html_select_time':
                    $t = self::getPara(substr($tag, 12), 0);

                    return '<?php echo self::html_select_time(' . self::makeArray($t) . '); ?>';
                    break;

                case 'function' :
                    $t = self::getPara(substr($tag, 8), false);

                    $out = "<?php \n" . '$k = ' . preg_replace("/(\'\\$[^,]+)/e", "stripslashes(trim('\\1','\''));", var_export($t, true)) . ";\n";
                    $out .= 'echo $k[\'name\'](';

                    $first = true;

                    foreach ($t as $n => $v) {
                        if ($n != "name") {
                            if ($first) {
                                $out .= '$k[\'' . $n . '\']';
                                $first = false;
                            } else {
                                $out .= ',$k[\'' . $n . '\']';
                            }
                        }
                    }
                    $out .= ');' . "\n?>";

                    return $out;
                    break;
                case 'url_wap' :
                    $reg_text = "/\"([^\"]+)\"/";
                    preg_match_all($reg_text, $tag, $matches);
                    if (count($matches[0]) > 0) {
                        //url格式正确
                        $param_str = "\"\"";
                        if (isset($matches[0][1]) && $matches[0][1] != '') {
                            //有额外传参
                            preg_match_all("/[$]([^\"&]+)/", $matches[0][1], $param_matches);
                            $replacement = array();
                            $finder = array();
                            if (count($param_matches[0]) > 0) {
                                foreach ($param_matches[0] as $m_item) {
                                    $finder[] = $m_item;
                                }
                                //有参数
                                foreach ($param_matches[1] as $p_item) {
                                    $p_item_arr = explode(".", $p_item);
                                    $var_str = '".self::$_var';
                                    foreach ($p_item_arr as $var_item) {
                                        $var_str = $var_str . "['" . $var_item . "']";
                                    }
                                    $var_str .= '."';
                                    $replacement[] = $var_str;
                                }
                            }
                            $param_str = str_replace($finder, $replacement, $matches[0][1]);
                        }

                        //$app_index = $matches[1][0];
                        $route = $matches[1][0];
                        if (empty($route))
                            $route = "index";


                        $code = "<?php\r\n";
                        $code .= "echo parse_url_tag_wap(\"";
                        $code .= "u:";
                        $code .= $route . "|\".";
                        $code .= $param_str . ".";
                        $code .= "\"\"); \r\n";
                        $code .= "?>";

                        return $code;
                    } else {
                        return '{' . $tag . '}';
                    }

                    break;

                case 'url' :
                    $reg_text = "/\"([^\"]+)\"/";
                    preg_match_all($reg_text, $tag, $matches);
                    if (count($matches[0]) > 0) {
                        //url格式正确
                        $param_str = "\"\"";
                        if (isset($matches[0][1]) && $matches[0][1] != '') {
                            //有额外传参
                            //return print_r($matches[0][2],true);
                            preg_match_all("/[$]([^\"&]+)/", $matches[0][1], $param_matches);
                            $replacement = array();
                            $finder = array();
                            if (count($param_matches[0]) > 0) {
                                foreach ($param_matches[0] as $m_item) {
                                    $finder[] = $m_item;
                                }
                                //有参数
                                foreach ($param_matches[1] as $p_item) {
                                    $p_item_arr = explode(".", $p_item);
                                    $var_str = '".self::$_var';
                                    foreach ($p_item_arr as $var_item) {
                                        $var_str = $var_str . "['" . $var_item . "']";
                                    }
                                    $var_str .= '."';
                                    $replacement[] = $var_str;
                                }
                            }
                            $param_str = str_replace($finder, $replacement, $matches[0][1]);
                        }

                        //$app_index = $matches[1][0];
                        $route = $matches[1][0];
                        if (empty($route))
                            $route = "index";


                        $code = "<?php\r\n";
                        $code .= "echo parse_url_tag(\"";
                        $code .= "u:";
                        $code .= $route . "|\".";
                        $code .= $param_str . ".";
                        $code .= "\"\"); \r\n";
                        $code .= "?>";

                        return $code;
                    } else {
                        return '{' . $tag . '}';
                    }

                    break;

                default:
                    return '{' . $tag . '}';
                    break;
            }
        }
    }

    /**
     * 处理smarty标签中的变量标签
     * @access  public
     * @param   string $val
     * @return  bool
     */
    private static function get_val($val)
    {
        if (strrpos($val, '[') !== false) {
            $val = preg_replace("/\[([^\[\]]*)\]/eis", "'.'.str_replace('$','\$','\\1')", $val);
        }

        if (strrpos($val, '|') !== false) {
            $moddb = explode('|', $val);
            $val = array_shift($moddb);
        }

        if (empty($val)) {
            return '';
        }

        if (strpos($val, '.$') !== false) {
            $all = explode('.$', $val);

            foreach ($all as $key => $val) {
                $all[$key] = $key == 0 ? self::makeVar($val) : '[' . self::makeVar($val) . ']';
            }
            $p = implode('', $all);
        } else {
            $p = self::makeVar($val);
        }

        if (!empty($moddb)) {
            foreach ($moddb as $key => $mod) {
                $s = explode(':', $mod);
                switch ($s[0]) {
                    case 'escape':
                        $s[1] = trim($s[1], '"');
                        if ($s[1] == 'html') {
                            $p = 'htmlspecialchars(' . $p . ')';
                        } elseif ($s[1] == 'url') {
                            $p = 'urlencode(' . $p . ')';
                        } elseif ($s[1] == 'decode_url') {
                            $p = 'urldecode(' . $p . ')';
                        } elseif ($s[1] == 'quotes') {
                            $p = 'addslashes(' . $p . ')';
                        } elseif ($s[1] == 'u8_url') {
                            if (EC_CHARSET != 'utf-8') {
                                $p = 'urlencode(ecs_iconv("' . EC_CHARSET . '", "utf-8",' . $p . '))';
                            } else {
                                $p = 'urlencode(' . $p . ')';
                            }
                        } else {
                            $p = 'htmlspecialchars(' . $p . ')';
                        }
                        break;

                    case 'nl2br':
                        $p = 'nl2br(' . $p . ')';
                        break;

                    case 'default':
                        $s[1] = $s[1]{0} == '$' ? self::get_val(substr($s[1], 1)) : "'$s[1]'";
                        $p = 'empty(' . $p . ') ? ' . $s[1] . ' : ' . $p;
                        break;

                    case 'truncate':
                        $p = 'sub_str(' . $p . ",$s[1])";
                        break;

                    case 'strip_tags':
                        $p = 'strip_tags(' . $p . ')';
                        break;

                    default:
                        # code...
                        break;
                }
            }
        }

        return $p;
    }


    /**
     * 处理去掉$的字符串
     * @access  public
     * @param   string $val
     * @return  bool
     */
    private static function makeVar($val)
    {
        if (strrpos($val, '.') === false) {
            if (isset(self::$_var[$val]) && isset(self::$_patchstack[$val])) {
                $val = self::$_patchstack[$val];
            }
            $p = 'self::$_var[\'' . $val . '\']';
        } else {
            $t = explode('.', $val);
            $_var_name = array_shift($t);
            if (isset(self::$_var[$_var_name]) && isset(self::$_patchstack[$_var_name])) {
                $_var_name = self::$_patchstack[$_var_name];
            }
            if ($_var_name == 'smarty') {
                $p = self::_compileSmartyRef($t);
            } else {
                $p = 'self::$_var[\'' . $_var_name . '\']';
            }
            foreach ($t as $val) {
                $p .= '[\'' . $val . '\']';
            }
        }

        return $p;
    }


    /**
     * 处理insert外部函数/需要include运行的函数的调用数据
     *
     * @access  public
     * @param   string $val
     * @param   int $type
     *
     * @return  array
     */
    private static function getPara($val, $type = 1) // 处理insert外部函数/需要include运行的函数的调用数据
    {
        $pa = self::strTrim($val);
        foreach ($pa as $value) {
            if (strrpos($value, '=')) {
                list($a, $b) = explode('=', str_replace(array(' ', '"', "'", '&quot;'), '', $value));

                if ($b{0} == '$') {
                    if ($type) {
                        eval('$para[\'' . $a . '\']=' . self::get_val(substr($b, 1)) . ';');
                    } else {
                        $para[$a] = self::get_val(substr($b, 1));
                    }
                } else {
                    $para[$a] = $b;
                }
            }
        }

        return $para;
    }

    /**
     * 处理if标签
     *
     * @access  public
     * @param   string $tag_args
     * @param   bool $elseif
     *
     * @return  string
     */
    private static function _compileIfTag($tag_args, $elseif = false)
    {
        preg_match_all('/\-?\d+[\.\d]+|\'[^\'|\s]*\'|"[^"|\s]*"|[\$\w\.]+|!==|===|==|!=|<>|<<|>>|<=|>=|&&|\|\||\(|\)|,|\!|\^|=|&|<|>|~|\||\%|\+|\-|\/|\*|\@|\S/', $tag_args, $match);

        $tokens = $match[0];
        // make sure we have balanced parenthesis
        $token_count = array_count_values($tokens);
        if (!empty($token_count['(']) && $token_count['('] != $token_count[')']) {
            // $this->_syntax_error('unbalanced parenthesis in if statement', E_USER_ERROR, __FILE__, __LINE__);
        }

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = &$tokens[$i];
            switch (strtolower($token)) {
                case 'eq':
                    $token = '==';
                    break;

                case 'ne':
                case 'neq':
                    $token = '!=';
                    break;

                case 'lt':
                    $token = '<';
                    break;

                case 'le':
                case 'lte':
                    $token = '<=';
                    break;

                case 'gt':
                    $token = '>';
                    break;

                case 'ge':
                case 'gte':
                    $token = '>=';
                    break;

                case 'and':
                    $token = '&&';
                    break;

                case 'or':
                    $token = '||';
                    break;

                case 'not':
                    $token = '!';
                    break;

                case 'mod':
                    $token = '%';
                    break;

                default:
                    if ($token[0] == '$') {
                        $token = self::get_val(substr($token, 1));
                    }
                    break;
            }
        }

        if ($elseif) {
            return '<?php elseif (' . implode(' ', $tokens) . '): ?>';
        } else {
            return '<?php if (' . implode(' ', $tokens) . '): ?>';
        }
    }

    /**
     * 处理foreach标签
     *
     * @access  public
     * @param   string $tag_args
     *
     * @return  string
     */
    private static function _compileForeachStart($tag_args)
    {
        $attrs = self::getPara($tag_args, 0);
        $from = $attrs['from'];
        if (isset(self::$_var[$attrs['item']]) && !isset(self::$_patchstack[$attrs['item']])) {
            self::$_patchstack[$attrs['item']] = $attrs['item'] . '_' . str_replace(array(' ', '.'), '_', microtime());
            $attrs['item'] = self::$_patchstack[$attrs['item']];
        } else {
            self::$_patchstack[$attrs['item']] = $attrs['item'];
        }
        $item = self::get_val($attrs['item']);

        if (!empty($attrs['key'])) {
            $key = $attrs['key'];
            $key_part = self::get_val($key) . ' => ';
        } else {
            $key = null;
            $key_part = '';
            $attrs['key'] = '';
        }

        if (!empty($attrs['name'])) {
            $name = $attrs['name'];
        } else {
            $name = null;
        }

        $output = '<?php ';
        $output .= "\$_from = $from; if (!is_array(\$_from) && !is_object(\$_from)) { settype(\$_from, 'array'); }; ";
        //$output .= "self::push_vars('".($attrs['key']?:'')."', '{$attrs['item']}');";

        if (!empty($name)) {
            $foreach_props = "self::\$_foreach['$name']";
            $output .= "{$foreach_props} = array('total' => count(\$_from), 'iteration' => 0);\n";
            $output .= "if ({$foreach_props}['total'] > 0):\n";
            $output .= "    foreach (\$_from as $key_part$item):\n";
            $output .= "        {$foreach_props}['iteration']++;\n";
        } else {
            $output .= "if (count(\$_from)):\n";
            $output .= "    foreach (\$_from as $key_part$item):\n";
        }
        return $output . '?>';
    }

    /**
     * 弹出临时数组的最后一个
     *
     * @return  void
     */
    private static function pop_vars()
    {
        $key = array_pop(self::$_temp_key);
        $val = array_pop(self::$_temp_val);

        if (!empty($key)) {
            eval($key);
        }
    }

    /**
     * 将 foreach 的 key, item 放入临时数组
     * @param  mixed $key
     * @param  mixed $val
     * @return  void
     */
    /* private static function push_vars($key='', $val='')
     {
         if (!empty($key)) {
             array_push(self::$_temp_key, "self::\$_var['$key']='" . self::$_var[$key] . "';");
         }
         if (!empty($val)) {
             array_push(self::$_temp_val, "self::\$_var['$val']='" . self::$_var[$val] . "';");
         }
     }*/


    private static function html_options($arr)
    {
        $selected = $arr['selected'];

        if ($arr['options']) {
            $options = (array)$arr['options'];
        } elseif ($arr['output']) {
            if ($arr['values']) {
                foreach ($arr['output'] as $key => $val) {
                    $options["{$arr[values][$key]}"] = $val;
                }
            } else {
                $options = array_values((array)$arr['output']);
            }
        }
        $out = '';
        if ($options) {
            foreach ($options as $key => $val) {
                $out .= $key == $selected ? "<option value=\"$key\" selected>$val</option>" : "<option value=\"$key\">$val</option>";
            }
        }

        return $out;
    }

    private static function html_select_date($arr)
    {
        $pre = $arr['prefix'];
        if (isset($arr['time'])) {
            if (intval($arr['time']) > 10000) {
                $arr['time'] = gmdate('Y-m-d', $arr['time'] + 8 * 3600);
            }
            $t = explode('-', $arr['time']);
            $year = strval($t[0]);
            $month = strval($t[1]);
            $day = strval($t[2]);
        }
        $now = gmdate('Y', self::$_nowtime);
        if (isset($arr['start_year'])) {
            if (abs($arr['start_year']) == $arr['start_year']) {
                $startyear = $arr['start_year'];
            } else {
                $startyear = $arr['start_year'] + $now;
            }
        } else {
            $startyear = $now - 3;
        }

        if (isset($arr['end_year'])) {
            if (strlen(abs($arr['end_year'])) == strlen($arr['end_year'])) {
                $endyear = $arr['end_year'];
            } else {
                $endyear = $arr['end_year'] + $now;
            }
        } else {
            $endyear = $now + 3;
        }

        $out = "<select name=\"{$pre}Year\">";
        for ($i = $startyear; $i <= $endyear; $i++) {
            $out .= $i == $year ? "<option value=\"$i\" selected>$i</option>" : "<option value=\"$i\">$i</option>";
        }
        if ($arr['display_months'] != 'false') {
            $out .= "</select>&nbsp;<select name=\"{$pre}Month\">";
            for ($i = 1; $i <= 12; $i++) {
                $out .= $i == $month ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
            }
        }
        if ($arr['display_days'] != 'false') {
            $out .= "</select>&nbsp;<select name=\"{$pre}Day\">";
            for ($i = 1; $i <= 31; $i++) {
                $out .= $i == $day ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
            }
        }

        return $out . '</select>';
    }

    private static function html_radios($arr)
    {
        $name = $arr['name'];
        $checked = $arr['checked'];
        $options = $arr['options'];

        $out = '';
        foreach ($options as $key => $val) {
            $out .= $key == $checked ? "<input type=\"radio\" name=\"$name\" value=\"$key\" checked>&nbsp;{$val}&nbsp;"
                : "<input type=\"radio\" name=\"$name\" value=\"$key\">&nbsp;{$val}&nbsp;";
        }

        return $out;
    }

    private static function html_select_time($arr)
    {
        $pre = $arr['prefix'];
        if (isset($arr['time'])) {
            $arr['time'] = gmdate('H-i-s', $arr['time'] + 8 * 3600);
            $t = explode('-', $arr['time']);
            $hour = strval($t[0]);
            $minute = strval($t[1]);
            $second = strval($t[2]);
        }
        $out = '';
        if (!isset($arr['display_hours'])) {
            $out .= "<select name=\"{$pre}Hour\">";
            for ($i = 0; $i <= 23; $i++) {
                $out .= $i == $hour ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
            }

            $out .= "</select>&nbsp;";
        }
        if (!isset($arr['display_minutes'])) {
            $out .= "<select name=\"{$pre}Minute\">";
            for ($i = 0; $i <= 59; $i++) {
                $out .= $i == $minute ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
            }

            $out .= "</select>&nbsp;";
        }
        if (!isset($arr['display_seconds'])) {
            $out .= "<select name=\"{$pre}Second\">";
            for ($i = 0; $i <= 59; $i++) {
                $out .= $i == $second ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">$i</option>";
            }

            $out .= "</select>&nbsp;";
        }

        return $out;
    }

    private static function cycle($arr)
    {
        static $k, $old;

        $value = explode(',', $arr['values']);
        if ($old != $value) {
            $old = $value;
            $k = 0;
        } else {
            $k++;
            if (!isset($old[$k])) {
                $k = 0;
            }
        }

        echo $old[$k];
    }

    private static function makeArray($arr)
    {
        $out = '';
        foreach ($arr as $key => $val) {
            if ($val{0} == '$') {
                $out .= $out ? ",'$key'=>$val" : "array('$key'=>$val";
            } else {
                $out .= $out ? ",'$key'=>'$val'" : "array('$key'=>'$val'";
            }
        }

        return $out . ')';
    }

    /**
     * 处理smarty开头的预定义变量
     * @access  public
     * @param   array $indexes
     * @return  string
     */
    private static function _compileSmartyRef(&$indexes)
    {
        $_ref = $indexes[0];

        switch ($_ref) {
            case 'now':
                $compiled_ref = 'time()';
                break;

            case 'foreach':
                array_shift($indexes);
                $_var = $indexes[0];
                $_propname = $indexes[1];
                switch ($_propname) {
                    case 'index':
                        array_shift($indexes);
                        $compiled_ref = "(self::\$_foreach['$_var']['iteration'] - 1)";
                        break;

                    case 'first':
                        array_shift($indexes);
                        $compiled_ref = "(self::\$_foreach['$_var']['iteration'] <= 1)";
                        break;

                    case 'last':
                        array_shift($indexes);
                        $compiled_ref = "(self::\$_foreach['$_var']['iteration'] == self::\$_foreach['$_var']['total'])";
                        break;

                    case 'show':
                        array_shift($indexes);
                        $compiled_ref = "(self::\$_foreach['$_var']['total'] > 0)";
                        break;

                    default:
                        $compiled_ref = "self::\$_foreach['$_var']";
                        break;
                }
                break;

            case 'get':
                $compiled_ref = '$_GET';
                break;

            case 'post':
                $compiled_ref = '$_POST';
                break;

            case 'cookies':
                $compiled_ref = '$_COOKIE';
                break;

            case 'env':
                $compiled_ref = '$_ENV';
                break;

            case 'server':
                $compiled_ref = '$_SERVER';
                break;

            case 'request':
                $compiled_ref = '$_REQUEST';
                break;

            case 'session':
                $compiled_ref = '$_SESSION';
                break;

            default:
                break;
        }
        array_shift($indexes);

        return $compiled_ref;
    }

    private static function smarty_insert_scripts($args)
    {
        static $scripts = array();

        $arr = explode(',', str_replace(' ', '', $args['files']));

        $str = '';
        foreach ($arr as $val) {
            if (in_array($val, $scripts) == false) {
                $scripts[] = $val;
                if ($val{0} == '.') {
                    $str .= '<script type="text/javascript" src="' . $val . '"></script>';
                } else {
                    $str .= '<script type="text/javascript" src="js/' . $val . '"></script>';
                }
            }
        }

        return $str;
    }

    private static function smarty_create_pages($params)
    {
        extract($params);

        if (empty($page)) {
            $page = 1;
        }

        if (!empty($count)) {
            $str = "<option value='1'>1</option>";
            $min = min($count - 1, $page + 3);
            for ($i = $page - 3; $i <= $min; $i++) {
                if ($i < 2) {
                    continue;
                }
                $str .= "<option value='$i'";
                $str .= $page == $i ? " selected='true'" : '';
                $str .= ">$i</option>";
            }
            if ($count > 1) {
                $str .= "<option value='$count'";
                $str .= $page == $count ? " selected='true'" : '';
                $str .= ">$count</option>";
            }
        } else {
            $str = '';
        }

        return $str;
    }

    private static function strTrim($str)
    {
        /* 处理'a=b c=d k = f '类字符串，返回数组 */
        while (strpos($str, '= ') != 0) {
            $str = str_replace('= ', '=', $str);
        }
        while (strpos($str, ' =') != 0) {
            $str = str_replace(' =', '=', $str);
        }

        return explode(' ', trim($str));
    }


    private static function _require($filename)
    {
        ob_start();
        ob_implicit_flush(0);
        include $filename;
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * 注册变量
     * @param $tplVar
     * @param string $value
     */
    public static function assign($tplVar, $value = '')
    {
        if (is_array($tplVar)) {
            foreach ($tplVar as $key => $val) {
                if ($key != '') {
                    self::$_var[$key] = $val;
                }
            }
        } else {
            if ($tplVar != '') {
                self::$_var[$tplVar] = $value;
            }
        }
    }

    /**
     * 验证模板文件的存在
     * @param $viewFile
     * @return string
     */
    public static function checkTemplate($viewFile)
    {
        $viewFolder = PUBLIC_PATH . 'themes' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR;
        if (empty($viewFile)) {
            self::$templateFile = $viewFolder . CONTROLLER . DIRECTORY_SEPARATOR . ACTION . VIEW_EXT;
        } else {
            $viewFileArr = explode(DIRECTORY_SEPARATOR, $viewFile);
            self::$templateFile = $viewFolder . $viewFileArr[0] . DIRECTORY_SEPARATOR . $viewFileArr[1];
        }
        if (file_exists(self::$templateFile)) {
            return self::$templateFile;
        } else {
            //抛出模板文件不存在异常
            echo "template file does not exist";
            exit();
        }
    }


}