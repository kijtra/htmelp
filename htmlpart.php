<?php

class htmlpart {
    private static $loaded = array();

    private static function load($name)
    {
        if (!empty(self::$loaded[$name])) {
            return self::$loaded[$name];
        }

        $class = __CLASS__.'_'.$name;
        return self::$loaded[$name] = new $class();
    }


    /* Meta Tag */

    public static function meta($arg1 = NULL, $arg2 = NULL, $arg3 = NULL)
    {
        $class = self::load('meta');
        if (NULL !== $arg1) {
            return $class->add($arg1, $arg2, $arg3);
        } else {
            return $class;
        }
    }

    public static function css($arg1 = NULL)
    {
        $class = self::load('meta');
        if (!$class->isCharacter($arg1)) {
            return false;
        } elseif(!$url = $class->normalize($arg1)) {
            return false;
        } elseif('.css' !== substr(strtolower($url), -4)) {
            return false;
        }
        return self::meta('stylesheet', $url);
    }

    public static function js($arg1 = NULL)
    {
        $class = self::load('meta');
        if (!$class->isCharacter($arg1)) {
            return false;
        } elseif(!$url = $class->normalize($arg1)) {
            return false;
        } elseif('.js' !== substr(strtolower($url), -3)) {
            return false;
        }
        return self::meta('javascript', $url);
    }


    /* Pagination */

    public static function pagination($total = NULL, $per_page = NULL)
    {
        $class = self::load('pagination');
        return $class->set($total, $per_page);
    }


    /* Breadcrumb */

    public static function bread($name = NULL, $url = NULL)
    {
        $class = self::load('breadcrumb');
        return $class->set($name, $url);
    }

    /* Output */

    public static function out($method = NULL, $return = TRUE)
    {
        $method = ('bread' === $method ? 'breadcrumb' : $method);

        if ('namespace' === $method) {
            $class = self::load('meta');
            return $class->display_namespace($return);
        } else {
            if(!$class = self::load($method)) {
                return NULL;
            }

            if (method_exists($class, 'display')) {
                return $class->display($return);
            }
        }
    }


    /* Helpers */

    protected function isEmpty(&$value)
    {
        return (NULL === $value || '' === $value);
    }

    protected function isCharacter(&$value)
    {
        return (!$this->isEmpty($value) && (is_string($value) || is_int($value) || is_float($value)));
    }

    protected function normalize(&$value)
    {
        if(!$this->isEmpty($value)) {
            if (is_bool($value)) {
                return ($value ? 'true' : 'false');
            } else {
                return (string)$value;
            }
        }
    }

    private static $url_parsed = NULL;
    protected function url_parse()
    {
        if (self::$url_parsed) {
            return self::$url_parsed;
        }

        $base = 'http'.($this->isSSL() ? 's' : NULL).'://';
        $base .= $_SERVER['HTTP_HOST'];

        $request = explode('?', $_SERVER['REQUEST_URI']);
        preg_match('#\A(.*?)(\.[a-zA-Z0-9]{2,4})?\z#', $request[0], $match);
        $path = $match[1];
        $basename = (!empty($match[2]) ? preg_replace('index\.[a-zA-Z0-9]{2,4}\z', NULL, $match[2]) : NULL);

        $query = NULL;
        $params = array();
        if (!empty($request[1])) {
            $query = '?'.$request[1];
            parse_str($request[1], $params);
        }

        return self::$url_parsed = array(
            'base' => $base,
            'path' => $path,
            'basename' => $basename,
            'query' => $query,
            'params' => $params,
        );
    }

    protected function isMobile()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return FALSE;
        }

        static $buffer = NULL;
        if ($buffer !== NULL) {
            return $buffer;
        }

        return $buffer = (preg_match("/(iPhone|iPod|iOS|Android.*?Mobile|Windows Mobile|symbian|BlackBerry)/i", $_SERVER['HTTP_USER_AGENT'] ) > 0);
    }

    protected function isSSL()
    {
        static $buffer = NULL;
        if (NULL !== $buffer) {
            return $buffer;
        }

        if (isset($_SERVER['HTTPS'])) {
            if ('on' === strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS']) {
                return $buffer = TRUE;
            }
        } elseif (isset($_SERVER['SERVER_PORT']) && '443' == $_SERVER['SERVER_PORT']) {
            return $buffer = TRUE;
        }

        return FALSE;
    }
}




/*
Meta Tag
*/

class htmlpart_meta extends htmlpart {
    private $namespace = array();
    private $tag_meta = array();
    private $tag_link = array();
    private $tag_script = array();

    private $ogp_single = array(
        'title', 'type', 'url', 'description', 'determiner', 'site_name',
    );

    private $ogp_multi = array(
        'image' => array('type', 'width', 'height', 'secure_url'),
        'video' => array('type', 'width', 'height', 'secure_url'),
        'audio' => array('type', 'secure_url'),
        'article' => array('published_time', 'modified_time', 'expiration_time', 'author', 'section', 'tag'),
        'book' => array('author', 'isbn', 'release_date', 'tag'),
        'profile' => array('first_name', 'last_name', 'username', 'gender'),
        'locale' => array('alternate'),
    );

    private $fb_single = array(
        'app_id',
    );


    function add($name = NULL, $value = NULL, $sub = NULL)
    {
        if (!$this->isCharacter($name)) {
            return FALSE;
        }

        if (FALSE !== strpos($name, ',')) {
            foreach(explode(',', $name) as $val) {
                $val = trim($val);
                $this->add($val, $value, $sub);
            }

            return NULL;
        }

        $lower = strtolower($name);
        if (0 !== strpos($lower,'x-')) {
            $name = $lower;
        }

        if (preg_match('/\A(og|fb|twitter):(.*)\z/', $name, $match)) {
            $this->addOgp($match[2], $value, $match[1]);
        } elseif (preg_match('/\A(canonical|alternate|stylesheet|prev|next)\z/', $name)) {
            $this->addLink($name, $value, $sub);
        } elseif (preg_match('/\A(javascript|js)\z/', $name) && $this->isCharacter($value)) {
            $this->set('script', 'js:'.$value, FALSE, array(
                'src' => $value,
            ));
        } else {
            $this->addMeta($name, $value, $sub);
        }
    }

    function data()
    {
        return array(
            'namespace' => $this->namespace,
            'meta' => array_values($this->tag_meta),
            'link' => array_values($this->tag_link),
            'script' => array_values($this->tag_script)
        );
    }

    function display($return = TRUE)
    {
        $lines = $sort = array();

        $data = self::data();
        foreach($data as $tag => $array) {
            if ('namespace' === $tag || empty($array)) {
                continue;
            }

            foreach($array as $props) {
                $str ='<'.$tag;
                foreach($props as $key => $val) {
                    if ('_' === $key) {
                        continue;
                    }

                    $str .= ' '.$key.'="'.$val.'"';
                }
                $lines[$tag] = (empty($lines[$tag]) ? array() : $lines[$tag]);
                $sort[$tag] = (empty($sort[$tag]) ? array() : $sort[$tag]);
                $lines[$tag][] = $str.'>';
                $sort[$tag][] = $props['_'];
            }
        }

        $html = NULL;
        foreach($lines as $tag => $array) {
            array_multisort($sort[$tag], SORT_STRING, $array);
            $html .= PHP_EOL.implode(PHP_EOL, $array);
        }

        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }

    function display_namespace($return = FALSE)
    {
        if (!empty($this->namespace)) {
            $str = NULL;
            foreach($this->namespace as $key => $val) {
                $str .= $key.': '.$val.' ';
            }
            $str = ' prefix="'.trim($str).'"';
            if ($return) {
                return $str;
            } else {
                echo $str;
            }
        }
    }

    private function set($tag, $key, $multi = FALSE, $data)
    {
        if ('meta' === $tag) {
            $data['_'] = $key;
            if ($multi) {
                $this->tag_meta[] = $data;
            } else {
                $this->tag_meta[$key] = $data;
            }
        } elseif ('link' === $tag) {
            $data['_'] = $key;
            if ($multi) {
                $this->tag_link[] = $data;
            } else {
                $this->tag_link[$key] = $data;
            }
        } elseif ('script' === $tag) {
            $data['_'] = $key;
            if ($multi) {
                $this->tag_script[] = $data;
            } else {
                $this->tag_script[$key] = $data;
            }
        }
    }

    private function addMeta($name, $value, $sub = NULL)
    {
        if ('keywords' === $name || 'keyword' === $name) {
            $name = 'keywords';
            if (empty($this->tag_meta[$name])) {
                $value = (is_array($value) ? implode(',', $value) : $value);
            } else {
                $value = $this->tag_meta[$name]['content'].','.(is_array($value) ? implode(',', $value) : $value);
            }
            $value = implode(',', array_unique(explode(',', $value)));
        }

        if ('refresh' === $name) {
            $sec = (is_numeric($value) ? $value : $sub);
            $url = (is_numeric($value) ? $sub : $value);
            if (!empty($url)) {
                $this->set('meta', $name, FALSE, array(
                    'http-equiv' => 'refresh',
                    'content' => $sec.';url='.$url
                ));
            }
        }

        elseif ($this->isCharacter($value)) {
            $is_multi = (preg_match('/\A(google)\z/', $name));
            if (preg_match('/\A(default-style|content-type|content-language)\z/', $name)) {
                $this->set('meta', $name, $is_multi, array(
                    'http-equiv' => $name,
                    'content' => $value
                ));
            } else {
                $this->set('meta', $name, $is_multi, array(
                    'name' => $name,
                    'content' => (is_bool($value) ? ($value ? 'true' : 'false') : $value)
                ));
            }
        }
    }

    private function addOgp($name, $value, $prefix = 'og')
    {

        if (FALSE !== array_search($name, $this->ogp_single)) {
            if(!$value = $this->normalize($value)) {
                return FALSE;
            }

            $this->namespace['og'] = 'http://ogp.me/ns/#';
            if ('fb' === $prefix) {
                $this->namespace[$prefix] = 'http://ogp.me/ns/'.$prefix.'#';
            }

            if ('website' === $name) {
                $this->namespace[$name] = 'http://ogp.me/ns/'.$name.'#';
            }

            $this->set('meta', $prefix.':'.$name, FALSE, array(
                'property' => $prefix.':'.$name,
                'content' => $value
            ));
        } elseif (!empty($this->ogp_multi[$name])) {
            if (is_array($value)) {
                $_name = NULL;
                foreach($value as $key => $val) {
                    $val = $this->normalize($val);
                    if (
                        !$val
                        || !$this->isCharacter($val)
                        || (FALSE === array_search($key, $this->ogp_multi[$name]) && 'url' !== $key && 'src' !== $key)
                    ) {
                        continue;
                    }

                    $_name = $prefix.':'.$name.(('url' === $key || 'src' === $key) ? NULL : ':'.$key);

                    $this->set('meta', $_name, FALSE, array(
                        'property' => $prefix.':'.$name,
                        'content' => $val
                    ));
                }

                if ($_name) {
                    $this->namespace['og'] = 'http://ogp.me/ns/#';
                    if ('fb' === $prefix) {
                        $this->namespace[$prefix] = 'http://ogp.me/ns/'.$prefix.'#';
                    }
                    $this->namespace[$name] = 'http://ogp.me/ns/'.$name.'#';
                }
            } elseif(!is_object($value)) {
                $this->namespace['og'] = 'http://ogp.me/ns/#';
                if ('fb' === $prefix) {
                    $this->namespace[$prefix] = 'http://ogp.me/ns/'.$prefix.'#';
                }
                $this->namespace[$name] = 'http://ogp.me/ns/'.$name.'#';
                $this->set('meta', $prefix.':'.$name, TRUE, array(
                    'property' => $prefix.':'.$name,
                    'content' => $this->normalize($value)
                ));
            }
        }
    }

    private function addLink($name, $value, $sub)
    {
        if ('alternate' === $name) {
            if (!empty($value) && !empty($sub)) {
                $type = strtolower(0 === strpos($value, 'http') ? $sub : $value);
                $url = strtolower(0 === strpos($value, 'http') ? $value : $sub);
                $data = array(
                    'rel' => $name,
                    'href' => $url
                );

                if ('handheld' === $type) {
                    $data['media'] = $type;
                } elseif (preg_match('/atom|rss|rdf/', $type)) {
                    $data['type'] = $type;
                } else {
                    $data['hreflang'] = $type;
                }

                self::$tag_link[] = $data;
                $this->set('link', $name, TRUE, $data);
            }
        } elseif($this->isCharacter($value)) {
            $_name = ('stylesheet' === $name ? $name.':'.$value : $name);
            $this->set('link', $_name, FALSE, array(
                'rel' => $name,
                'href' => $this->normalize($value)
            ));
        }
    }
}


/*
Pagination
*/

class htmlpart_pagination extends htmlpart {
    private $options = array(
        'total' => 0,
        'per_page' => 10,
        'current_page' => 1,
        'num_links' => 2,

        'prefix' => NULL,
        'suffix' => NULL,
        'query_key' => 'page',

        'edge_style' => NULL,//'number' or 'button' or NULL
        'mobile' => 'auto',
        'relative_link' => FALSE,
        'wrap_start' => '<div class="pagination-wrap">',
        'wrap_end' => '</div>',
        'number_divider' => '<span style="border-top:0;border-bottom:0;padding-left:3px;padding-right:3px;color:#999;background:transparent;">…</span>',
        'labels' => array(
            'first' => '&lt;&lt;',
            'prev' => '&lt; PREV',
            'next' => 'NEXT &gt;',
            'last' => '&gt;&gt;',
            'select' => 'Page {num}',
        ),
        'classes' => array(
            'first' => 'first',
            'prev' => 'prev',
            'next' => 'next',
            'last' => 'last',
            'link' => 'link',
        ),
        'attrs' => array(
            'first' => NULL,
            'prev' => NULL,
            'next' => NULL,
            'last' => NULL,
            'link' => NULL,
        ),
    );

    private $total_pages = 1;
    private $url_parts = NULL;
    private $datas = NULL;
    private $html = NULL;

    function set($arg1 = NULL, $arg2 = NULL)
    {
        if (!empty($arg1)) {
            if (is_array($arg1)) {
                $this->option($arg1);
            } elseif(ctype_digit(strval($arg1))) {
                $options = array('total' => (int)$arg1);
                if ($arg2 && ctype_digit(strval($arg2))) {
                    $options['per_page'] = (int)$arg2;
                }
                $this->option($options);
            }
        }

        $this->urlParse();
        return $this;
    }

    function wrap($start = NULL, $end = NULL)
    {
        $options['wrap_start'] = $start;
        $options['wrap_end'] = $end;
        $this->option($options);
        return $this;
    }

    function option($options = array())
    {
        if (!empty($options)) {
            $this->options = array_replace_recursive($this->options, $options);
        }
        $this->buildData();
        return $this;
    }


    private function urlParse()
    {
        if (!empty($this->url_parts)) {
            return $this->url_parts;
        }

        $parse = $this->url_parse();

        $ops = $this->options;
        $base_url = NULL;
        $query_string = $parse['query'];
        $before = NULL;
        $after = NULL;

        $current_uri = $parse['path'];
        $get = $parse['params'];

        if(!empty($ops['prefix']) || !empty($ops['suffix'])) {
            preg_match('#\A(.*?)(/'.preg_quote($ops['prefix']).'(\d+)'.preg_quote($ops['suffix']).')([/\?].*)?\z#', $current_uri.$query_string, $m);
            if (!empty($m[3])) {
                $base_url = $m[1];
                $this->options['current_page'] = (int)$m[3];
            } else {
                $base_url = $current_uri;
            }
            $before = rtrim($base_url, '/').'/'.$ops['prefix'];
            $after = $ops['suffix'].$query_string;
        } else {
            $query_key = (!empty($ops['query_key']) ? $ops['query_key'] : 'page');
            $base_url = $current_uri;
            $before = $base_url.$query_string.($query_string ? '&' : '?').$query_key.'=';
            if (array_key_exists($query_key, $get)) {
                if (!empty($get[$query_key]) && $get[$query_key] > 1) {
                    $this->options['current_page'] = (int)$get[$query_key];
                }
                unset($get[$query_key]);
                $get = array_filter($get);
                if (!empty($get)) {
                    $query_string = '?'.http_build_query($get);
                    $before = $base_url.$query_string.'&'.$query_key.'=';
                } else {
                    $query_string = null;
                }
            }
        }


        $site = ($ops['relative_link']) ? '/' : $parse['base'].'/';
        $base_url = ltrim($base_url, '/');
        $data = array(
            'base' => $site.$base_url,
            'query' => $query_string,
            'first' => $site.$base_url.$query_string,
            'before' => $site.ltrim($before, '/'),
            'after' => $after
        );

        $this->url_parts = $data;
        return $data;
    }


    private function buildData()
    {
        $ops = $this->options;
        $urls = $this->url_parts;

        if (empty($ops['total']) || empty($ops['per_page']) || $ops['total'] <= $ops['per_page']) {
            return NULL;
        }

        $current = $ops['current_page'];
        $pages = $this->total_pages = ceil($ops['total'] / $ops['per_page']);

        $datas = array();

        // 最初
        if (!empty($ops['edge_style'])) {
            $datas['first'] = array(
                'page' => 1,
                'link' => $urls['first'],
                'label' => ('button' == $ops['edge_style'] ? $ops['labels']['first'] : 1),
                'class' => $ops['classes']['first'],
                'attr' => $ops['attrs']['first'],
                'disabled' => !($current > 1 && ($current - ($ops['num_links'] + 1)) > 0),
            );
        }

        // 前へ
        $datas['prev'] = array(
            'page' => null,
            'link' => null,
            'label' => $ops['labels']['prev'],
            'class' => $ops['classes']['prev'],
            'attr' => $ops['attrs']['prev'],
            'disabled' => true,
        );
        if ($current > 1) {
            $datas['prev']['disabled'] = false;
            if (2 === $current) {
                $datas['prev']['page'] = 1;
                $datas['prev']['link'] = $urls['first'];
            } else {
                $datas['prev']['page'] = ($current - 1);
                $datas['prev']['link'] = $urls['before'].$datas['prev']['page'].$urls['after'];
            }
            parent::meta('prev', $datas['prev']['link']);
        }

        // 次へ
        $datas['next'] = array(
            'page' => null,
            'link' => null,
            'label' => $ops['labels']['next'],
            'class' => $ops['classes']['next'],
            'attr' => $ops['attrs']['next'],
            'disabled' => true,
        );
        if ($current < $pages) {
            $datas['next']['disabled'] = false;
            if (($current + 1) >= $pages) {
                $datas['next']['page'] = $pages;
                $datas['next']['link'] = $urls['before'].$pages.$urls['after'];
            } else {
                $datas['next']['page'] = ($current + 1);
                $datas['next']['link'] = $urls['before'].$datas['next']['page'].$urls['after'];
            }
            parent::meta('next', $datas['next']['link']);
        }

        // 最後
        if (!empty($ops['edge_style'])) {
            $datas['last'] = array(
                'page' => $pages,
                'link' => $urls['before'].$pages.$urls['after'],
                'label' => ('button' == $ops['edge_style'] ? $ops['labels']['last'] : $pages),
                'class' => $ops['classes']['last'],
                'attr' => $ops['attrs']['last'],
                'disabled' => !(($current + $ops['num_links']) < $pages),
            );
        }

        $links = null;
        if (!empty($ops['num_links'])) {
            $links = array();

            $min = (($current - $ops['num_links']) > 0) ? $current - $ops['num_links'] : 1;
    		$max = (($current + $ops['num_links']) < $pages) ? $current + $ops['num_links'] : $pages;
            if ($current - $ops['num_links'] < 1) {
                $max += ($ops['num_links'] + 1) - $current;
            } elseif ($current + $ops['num_links'] > $pages) {
                $min -= ($current + $ops['num_links']) - $pages;
            }
            if ($min < 1) {
                $min = 1;
            }
            if ($max > $pages) {
                $max = $pages;
            }

            foreach(range($min, $max) as $num) {
                if ($num > $pages) {
                    break;
                }

                $link = array(
                    'page' => $num,
                    'link' => $urls['before'].$num.$urls['after'],
                    'label' => $num,
                    'class' => $ops['classes']['link'],
                    'attr' => null,
                    'current' => ($current == $num)
                );
                if (1 == $num) {
                    $link['link'] = $urls['first'];
                }
                $links[] = $link;
            }
        }

        $datas['links'] = (!empty($links)) ? $links : null;

        return $this->datas = $datas;
    }

    function display($return = FALSE)
    {
        $ops = $this->options;
        if(!$datas = $this->datas) {
            return NULL;
        }

        $html = NULL;

        $is_mobile = FALSE;

        if (!empty($ops['mobile'])) {
            if ('auto' === strtolower($ops['mobile'])) {
                $is_mobile = $this->isMobile();
            } elseif(is_bool($ops['mobile'])) {
                $is_mobile = $ops['mobile'];
            }
        }

        if (!$this->isMobile()) {
            $html .= '<ul class="pagination">';

            if ('button' == $ops['edge_style']) {
                $html .= '<li'.($datas['first']['disabled'] ? ' class="disabled"' : NULL).'>';
                $html .= '<a href="'.($datas['first']['disabled'] ? '#' : $datas['first']['link']).'">'.$datas['first']['label'].'</a>';
                $html .= '</li>';
            }

            $html .= '<li'.($datas['prev']['disabled'] ? ' class="disabled"' : NULL).'>';
            $html .= '<a href="'.($datas['prev']['disabled'] ? '#' : $datas['prev']['link']).'">'.$datas['prev']['label'].'</a>';
            $html .= '</li>';

            if ('number' == $ops['edge_style'] && !$datas['first']['disabled']) {
                $html .= '<li'.($datas['first']['disabled'] ? ' class="disabled"' : NULL).'>';
                $html .= '<a href="'.($datas['first']['disabled'] ? '#' : $datas['first']['link']).'">'.$datas['first']['label'].'</a>';
                $html .= '</li>';
                $html .= '<li>'.$ops['number_divider'].'</li>';
            }

            if (!empty($datas['links'])) {
                foreach($datas['links'] as $val) {
                    $html .= '<li'.($val['current'] ? ' class="active"' : NULL).'><a href="'.$val['link'].'">'.$val['label'].'</a></li>';
                }
            }

            if ('number' == $ops['edge_style'] && !$datas['last']['disabled']) {
                $html .= '<li>'.$ops['number_divider'].'</li>';
                $html .= '<li'.($datas['last']['disabled'] ? ' class="disabled"' : NULL).'>';
                $html .= '<a href="'.($datas['last']['disabled'] ? '#' : $datas['last']['link']).'">'.$datas['last']['label'].'</a>';
                $html .= '</li>';
            }

            $html .= '<li'.($datas['next']['disabled'] ? ' class="disabled"' : NULL).'>';
            $html .= '<a href="'.($datas['next']['disabled'] ? '#' : $datas['next']['link']).'">'.$datas['next']['label'].'</a>';
            $html .= '</li>';

            if ('button' == $ops['edge_style']) {
                $html .= '<li'.($datas['last']['disabled'] ? ' class="disabled"' : NULL).'>';
                $html .= '<a href="'.($datas['last']['disabled'] ? '#' : $datas['last']['link']).'">'.$datas['last']['label'].'</a>';
                $html .= '</li>';
            }

            $html .= '</ul>';
        }

        else {
            $html .= '<div class="input-group">';

            $html .= '<span class="input-group-btn">';
            $html .= '<a href="'.($datas['prev']['disabled'] ? '#' : $datas['prev']['link']).'" class="btn btn-default'.($datas['prev']['disabled'] ? ' disabled' : NULL).'">'.$datas['prev']['label'].'</a>';
            $html .= '</span>';


            $html .= '<select class="form-control" onchange="window.location.href=this.value">';
            $url = $this->url_parts;
            $current = $ops['current_page'];
            foreach(range(1, $this->total_pages) as $num) {
                $selected = ($num == $current ? ' selected="selected"' : NULL);
                if ($ops['labels']['select']) {
                    $label = str_replace('{num}', $num, $ops['labels']['select']);
                } else {
                    $label = $num;
                }
                $html .= '<option value="'.$url['before'].$num.$url['after'].'"'.$selected.'>'.$label.'</option>';
            }
            $html .= '</select>';


            $html .= '<span class="input-group-btn">';
            $html .= '<a href="'.($datas['next']['disabled'] ? '#' : $datas['next']['link']).'" class="btn btn-default'.($datas['next']['disabled'] ? ' disabled' : NULL).'">'.$datas['next']['label'].'</a>';
            $html .= '</span>';

            $html .= '</div>';
        }

        $this->html = $ops['wrap_start'].$html.$ops['wrap_end'];

        if ($return) {
            return $this->html;
        } else {
            echo $this->html;
        }
    }
}


/* Breadcrumb */

class htmlpart_breadcrumb extends htmlpart {
    private $datas = array();
    private $wrap_start = NULL;
    private $wrap_end = NULL;

    function set($name = NULL, $url = NULL)
    {
        if (empty($name) || empty($url) || !$this->isCharacter($name) || !$this->isCharacter($url)) {
            return false;
        }

        $parse = $this->url_parse();

        if (false !== strpos($url, '..')) {
            if (!empty($parse['path'])) {
                $query = explode('?', $url);
                $query = (!empty($query[1]) ? '?'.$query[1] : NULL);
                $is_current_dir = ('/' == substr($parse['path'], -1));
                $count = substr_count($url, '../');
                $dirs = explode('/', trim($parse['path'], '/'));
                $sliced = array_slice($dirs, 0, -($is_current_dir ? $count - 1 : $count));
                if (!empty($sliced)) {
                    $path = '/'.implode('/', $sliced).($is_current_dir ? '/' : NULL);
                    $url = $path.$query;
                } else {
                    $url = '/'.$query;
                }
            }
        }

        if (empty($this->datas[$url])) {
            if ('/' === $url{0}) {
                $url = $parse['base'].$url;
            }
            $this->datas[$url] = $name;
        }
    }

    function wrap($start = NULL, $end = NULL)
    {
        $this->wrap_start = $start;
        $this->wrap_end = $end;
        return $this;
    }

    function display($return = TRUE)
    {
        if (!$datas = $this->datas) {
            return false;
        }

        $last = array_keys($this->datas);
        $last = end($last);

        $html = $this->wrap_start;
        $html .= '<ol class="breadcrumb" xmlns:v="http://rdf.data-vocabulary.org/#">';

        $first = true;
        foreach($this->datas as $url => $name) {
            $class = ($first ? 'first' : NULL);
            $class .= ($url === $last ? ' active last' : NULL);
            $html .= '<li'.($class ? ' class="'.trim($class).'"' : NULL).' typeof="v:Breadcrumb"><a href="'.$url.'" rel="v:url">'.$name.'</a></li>';
            $first = false;
        }

        $html .= '</ol>';
        $html .= $this->wrap_end;

        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }
}
