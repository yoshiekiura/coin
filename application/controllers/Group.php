<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Group class
 *
 * Copyright (c) CIBoard <www.ciboard.co.kr>
 *
 * @author CIBoard (develop@ciboard.co.kr)
 */

/**
 * 게시판 그룹 메인을 담당하는 controller 입니다.
 */
class Group extends CB_Controller
{

    /**
     * 모델을 로딩합니다
     */
    protected $models = array('Board');

    /**
     * 헬퍼를 로딩합니다
     */
    protected $helpers = array('form', 'array');

    function __construct()
    {
        parent::__construct();

        /**
         * 라이브러리를 로딩합니다
         */
        if ( ! $this->member->item('mem_password') && $this->member->item('mem_id')) {
            redirect('membermodify');
        }
        
        $this->load->library(array('querystring', 'board_group','coin'));
    }


    /**
     * 게시판 그룹 페이지입니다
     */
    public function index($bgr_key = '',$brd_key = '')
    {
        // 이벤트 라이브러리를 로딩합니다
        $eventname = 'event_group_index';
        $this->load->event($eventname);

        if (empty($bgr_key)) {
            show_404();
        }

        $view = array();
        $view['view'] = array();

        // 이벤트가 존재하면 실행합니다
        $view['view']['event']['before'] = Events::trigger('before', $eventname);

        $group_id = $this->board_group->item_key('bgr_id', $bgr_key);
        if (empty($group_id)) {
            show_404();
        }

        $group = $this->board_group->item_all($group_id);

        $select = 'brd_id';
        $where = array(
            'bgr_id' => element('bgr_id', $group),
            'brd_search' => 1,
        );
        $board_id = $this->Board_model->get_board_list($where);
        $board_list = array();


        if ($board_id && is_array($board_id)) {
            foreach ($board_id as $key => $val) {
                
                if($key===1 && $bgr_key==='other') $board_list[]=array("brd_key"=>"attendance","board_name"=>"출석체크");
                
                $board_list[] = $this->board->item_all(element('brd_id', $val));
                if($key===0 && $bgr_key==='g-c') $board_list[]=array("brd_key"=>"live_news_sub","board_name"=>"인기뉴스");
            }
        }

        $group['headercontent'] = ($this->cbconfig->get_device_view_type() === 'mobile')
            ? element('mobile_header_content', $group)
            : element('header_content', $group);
         
        $group['footercontent'] = ($this->cbconfig->get_device_view_type() === 'mobile')
            ? element('mobile_footer_content', $group)
            : element('footer_content', $group);
         
        $view['view']['group'] = $group;

        $view['view']['board_list'] = $board_list;

        $view['view']['canonical'] = group_url($bgr_key);

        // 이벤트가 존재하면 실행합니다
        $view['view']['event']['before_layout'] = Events::trigger('before_layout', $eventname);

        $view['view']['view_coin'] = $this->get_coin_data();

        /**
         * 레이아웃을 정의합니다
         */
        $page_title = $this->cbconfig->item('site_meta_title_group');
        $meta_description = $this->cbconfig->item('site_meta_description_group');
        $meta_keywords = $this->cbconfig->item('site_meta_keywords_group');
        $meta_author = $this->cbconfig->item('site_meta_author_group');
        $page_name = $this->cbconfig->item('site_page_name_group');

        $searchconfig = array(
            '{그룹명}',
            '{그룹아이디}',
        );
        $replaceconfig = array(
            element('bgr_name', $group),
            $bgr_key,
        );

        $page_title = str_replace($searchconfig, $replaceconfig, $page_title);
        $meta_description = str_replace($searchconfig, $replaceconfig, $meta_description);
        $meta_keywords = str_replace($searchconfig, $replaceconfig, $meta_keywords);
        $meta_author = str_replace($searchconfig, $replaceconfig, $meta_author);
        $page_name = str_replace($searchconfig, $replaceconfig, $page_name);

        $layout_dir = element('group_layout', $group) ? element('group_layout', $group) : $this->cbconfig->item('layout_group');
        $mobile_layout_dir = element('group_mobile_layout', $group) ? element('group_mobile_layout', $group) : $this->cbconfig->item('mobile_layout_group');
        $use_sidebar = element('group_sidebar', $group) ? element('group_sidebar', $group) : $this->cbconfig->item('sidebar_group');
        $use_mobile_sidebar = element('group_mobile_sidebar', $group) ? element('group_mobile_sidebar', $group) : $this->cbconfig->item('mobile_sidebar_group');
        $skin_dir = element('group_skin', $group) ? element('group_skin', $group) : $this->cbconfig->item('skin_group');
        $mobile_skin_dir = element('group_mobile_skin', $group) ? element('group_mobile_skin', $group) : $this->cbconfig->item('mobile_skin_group');
        $layoutconfig = array(
            'path' => 'group',
            'layout' => 'layout',
            'skin' => 'group',
            'layout_dir' => $layout_dir,
            'mobile_layout_dir' => $mobile_layout_dir,
            'use_sidebar' => $use_sidebar,
            'use_mobile_sidebar' => $use_mobile_sidebar,
            'skin_dir' => $skin_dir,
            'mobile_skin_dir' => $mobile_skin_dir,
            'page_title' => $page_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'meta_author' => $meta_author,
            'page_name' => $page_name,
        );
        $view['layout'] = $this->managelayout->front($layoutconfig, $this->cbconfig->get_device_view_type());
        $this->data = $view;
        $this->layout = element('layout_skin_file', element('layout', $view));
        $this->view = element('view_skin_file', element('layout', $view));
    }

    function view_board($brd_key,$more=0,$post_notice=0){
        if($this->cbconfig->get_device_view_type()==='desktop'){
            if($brd_key==='live_news'){
                $config = array(
                    'skin' => 'basic',            
                    'brd_key' => $brd_key,
                    'limit' => 6,
                    'length' => 40,
                    'is_gallery'=> 1,
                    'image_width'=> 120,
                    'image_height'=> 90,

                );            
            } else {
                
                $config = array(
                    'skin' => 'basic',            
                    'brd_key' => $brd_key,
                    'limit' => 5,
                    'length' => 40,
                );
            }
            echo $this->board->latest_group_desktop($config,$more);
        } else {
            if($brd_key==='live_news'){
                $config = array(
                    'skin' => 'mobile',            
                    'brd_key' => $brd_key,
                    'limit' => 6,
                    'length' => 40,
                    'is_gallery'=> 1,
                    'image_width'=> 120,
                    'image_height'=> 90,
                    'post_notice'=> $post_notice,
                );
            }elseif($brd_key==='w-1' || $brd_key==='w-2' || $brd_key==='w-3'){
                
                $config = array(
                    'skin' => 'mobile',            
                    'brd_key' => $brd_key,
                    'limit' => 27,
                    'length' => 40,
                    'is_gallery'=> 1,
                    'image_width'=> 120,
                    'image_height'=> 90,
                    
                );
            }  else {
                
                $config = array(
                    'skin' => 'mobile',            
                    'brd_key' => $brd_key,
                    'limit' => 5,
                    'length' => 40,
                );
            }
            echo $this->board->latest_group($config,$more);
        }
    }

    function get_coin_data($cur_unit=''){
        
        $this->cbconfig->get_device_view_type();
        $config = array(
            'skin' => 'mobile',
            'cur_unit' => $cur_unit,
            
        );
        return $this->coin->all_price($config);
    }
}
