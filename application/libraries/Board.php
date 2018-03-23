<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Board class
 *
 * Copyright (c) CIBoard <www.ciboard.co.kr>
 *
 * @author CIBoard (develop@ciboard.co.kr)
 */

/**
 * board table 을 주로 관리하는 class 입니다.
 */
class Board extends CI_Controller
{

    private $CI;
    private $board_id;
    private $board_key;
    private $group;
    private $admin;
    private $call_admin;

    function __construct()
    {
        $this->CI = & get_instance();
    }


    /**
     * board table 의 정보를 얻습니다
     */
    public function get_board($brd_id = 0, $brd_key = '')
    {
        if (empty($brd_id) && empty($brd_key)) {
            return false;
        }

        if ($brd_id) {
            $this->CI->load->model('Board_model');
            $board = $this->CI->Board_model->get_one($brd_id);
        } elseif ($brd_key) {
            $where = array(
                'brd_key' => $brd_key,
            );
            $this->CI->load->model('Board_model');
            $board = $this->CI->Board_model->get_one('', '', $where);
        } else {
            return false;
        }
        $board['board_name'] = ($this->CI->cbconfig->get_device_view_type() === 'mobile' && $board['brd_mobile_name'])
            ? $board['brd_mobile_name'] : $board['brd_name'];
        if (element('brd_id', $board)) {
            $board_meta = $this->get_all_meta(element('brd_id', $board));
            if (is_array($board_meta)) {
                $board = array_merge($board, $board_meta);
            }
        }

        if (element('brd_id', $board)) {
            $this->board_id[element('brd_id', $board)] = $board;
        }
        if (element('brd_key', $board)) {
            $this->board_key[element('brd_key', $board)] = $board;
        }
    }


    /**
     * board meta table 의 정보를 얻습니다
     */
    public function get_all_meta($brd_id = 0)
    {
        $brd_id = (int) $brd_id;
        if (empty($brd_id) OR $brd_id < 1) {
            return false;
        }
        $this->CI->load->model('Board_meta_model');
        $result = $this->CI->Board_meta_model->get_all_meta($brd_id);

        return $result;
    }


    /**
     * board group meta table 의 정보를 얻습니다
     */
    public function get_all_group_meta($bgr_id = 0)
    {
        $bgr_id = (int) $bgr_id;
        if (empty($bgr_id) OR $bgr_id < 1) {
            return false;
        }
        $this->CI->load->model('Board_group_meta_model');
        $result = $this->CI->Board_group_meta_model->get_all_meta($bgr_id);

        return $result;
    }


    /**
     * item 을 brd_id 에 기반하여 얻습니다
     */
    public function item_id($column = '', $brd_id = 0)
    {
        if (empty($column)) {
            return false;
        }
        $brd_id = (int) $brd_id;
        if (empty($brd_id) OR $brd_id < 1) {
            return false;
        }
        if ( ! isset($this->board_id[$brd_id])) {
            $this->get_board($brd_id, '');
        }
        if ( ! isset($this->board_id[$brd_id])) {
            return false;
        }
        $board = $this->board_id[$brd_id];

        return isset($board[$column]) ? $board[$column] : false;
    }


    /**
     * item 을 brd_key 에 기반하여 얻습니다
     */
    public function item_key($column = '', $brd_key = '')
    {

        if (empty($column)) {
            return false;
        }
        if (empty($brd_key)) {
            return false;
        }
        if ( ! isset($this->board_key[$brd_key])) {
            $this->get_board('', $brd_key);
        }
        if ( ! isset($this->board_key[$brd_key])) {
            return false;
        }
        $board = $this->board_key[$brd_key];

        return isset($board[$column]) ? $board[$column] : false;
    }


    /**
     * 모든 item 을 brd_id 에 기반하여 얻습니다
     */
    public function item_all($brd_id = 0)
    {
        $brd_id = (int) $brd_id;
        if (empty($brd_id) OR $brd_id < 1) {
            return false;
        }
        if ( ! isset($this->board_id[$brd_id])) {
            $this->get_board($brd_id, '');
        }
        if ( ! isset($this->board_id[$brd_id])) {
            return false;
        }

        return $this->board_id[$brd_id];
    }


    /**
     * 그룹 정보를 얻습니다
     */
    public function get_group($bgr_id = 0)
    {
        $bgr_id = (int) $bgr_id;
        if (empty($bgr_id) OR $bgr_id < 1) {
            return false;
        }
        if ($bgr_id) {
            $this->CI->load->model('Board_group_model');
            $group = $this->CI->Board_group_model->get_one($bgr_id);
        } else {
            return false;
        }

        $group_meta = $this->get_all_group_meta($bgr_id);
        if (is_array($group_meta)) {
            $group = array_merge($group, $group_meta);
        }
        $this->group[$bgr_id] = $group;
    }


    /**
     * 게시글 삭제시 삭제되어야하는 모든 테이블데이터입니다
     */
    public function delete_post($post_id = 0)
    {
        $post_id = (int) $post_id;
        if (empty($post_id) OR $post_id < 1) {
            return false;
        }

        $this->CI->load->model(
            array(
                'Post_model', 'Blame_model', 'Like_model',
                'Post_extra_vars_model', 'Post_file_model', 'Post_file_download_log_model',
                'Post_history_model', 'Post_link_model', 'Post_link_click_log_model',
                'Post_meta_model', 'Post_tag_model', 'Scrap_model',
                'Comment_model'
            )
        );

        $post = $this->CI->Post_model->get_one($post_id);

        if ( ! element('post_id', $post)) {
            return false;
        }

        $board = $this->CI->board->item_all(element('brd_id', $post));

        $this->CI->Post_model->delete($post_id);

        $deletewhere = array(
            'target_id' => $post_id,
            'target_type' => 1,
         );
        $this->CI->Blame_model->delete_where($deletewhere);
        $this->CI->Like_model->delete_where($deletewhere);

        $deletewhere = array(
            'post_id' => $post_id,
        );

        // 첨부 파일 삭제
        $postfiles = $this->CI->Post_file_model->get('', '', $deletewhere);
        if ($postfiles) {
            foreach ($postfiles as $postfile) {
                @unlink(config_item('uploads_dir') .  '/post/' . element('pfi_filename', $postfile));
            }
        }

        $this->CI->Post_extra_vars_model->delete_where($deletewhere);
        $this->CI->Post_file_model->delete_where($deletewhere);
        $this->CI->Post_file_download_log_model->delete_where($deletewhere);
        $this->CI->Post_history_model->delete_where($deletewhere);
        $this->CI->Post_link_model->delete_where($deletewhere);
        $this->CI->Post_link_click_log_model->delete_where($deletewhere);
        $this->CI->Post_meta_model->deletemeta($post_id);
        $this->CI->Post_tag_model->delete_where($deletewhere);
        $this->CI->Scrap_model->delete_where($deletewhere);

        $where = array(
            'post_id' => $post_id,
        );
        $comment = $this->CI->Comment_model->get('', 'cmt_id', $where);
        if ($comment && is_array($comment)) {
            foreach ($comment as $cval) {
                if (element('cmt_id', $cval)) {
                    $this->delete_comment(element('cmt_id', $cval));
                }
            }
        }
        $this->CI->load->library('point');
        $this->CI->point->delete_point(
            abs(element('mem_id', $post)),
            'post',
            $post_id,
            '게시글 작성'
        );
        if (element('point_post_delete', $board)
            && $this->CI->member->item('mem_id') === abs(element('mem_id', $post))) {

            $point_delete = 0 - abs(element('point_post_delete', $board));

            $this->CI->point->insert_point(
                abs(element('mem_id', $post)),
                $point_delete,
                element('brd_name', $board) . ' ' . $post_id . ' 게시글 삭제',
                'post_delete',
                $post_id,
                '게시글 삭제'
            );

        } elseif (element('point_admin_post_delete', $board)
            && $this->CI->member->item('mem_id') !== abs(element('mem_id', $post))) {
            $point_delete = 0 - abs(element('point_admin_post_delete', $board));

            $this->CI->point->insert_point(
                abs(element('mem_id', $post)),
                $point_delete,
                element('brd_name', $board) . ' ' . $post_id . ' 게시글 삭제',
                'admin_post_delete',
                $post_id,
                '게시글 삭제'
            );
        }

        return true;
    }


    /**
     * 코멘트 삭제시 삭제되어야하는 모든 테이블데이터입니다
     */
    public function delete_comment($cmt_id = 0)
    {
        $cmt_id = (int) $cmt_id;
        if (empty($cmt_id) OR $cmt_id < 1) {
            return false;
        }

        $this->CI->load->model( array('Post_model', 'Blame_model', 'Like_model', 'Comment_model', 'Comment_meta_model'));

        $comment = $this->CI->Comment_model->get_one($cmt_id);

        if ( ! element('cmt_id', $comment)) {
            return false;
        }

        $post = $this->CI->Post_model->get_one(element('post_id', $comment));
        $board = $this->CI->board->item_all(element('brd_id', $post));

        $this->CI->Comment_model->delete($cmt_id);
        $deletewhere = array(
            'target_id' => $cmt_id,
            'target_type' => 2,
        );
        $this->CI->Blame_model->delete_where($deletewhere);
        $this->CI->Like_model->delete_where($deletewhere);

        $deletewhere = array(
            'cmt_id' => $cmt_id,
        );
        $this->CI->Comment_meta_model->delete_where($deletewhere);
        $this->CI->Post_model->update_plus(element('post_id', $comment), 'post_comment_count', '-1');

        $this->CI->load->library('point');
        $this->CI->point->delete_point(
            abs(element('mem_id', $comment)),
            'comment',
            $cmt_id,
            '댓글 작성'
        );
        $this->CI->point->delete_point(
            abs(element('mem_id', $comment)),
            'lucky-comment',
            $cmt_id,
            '럭키포인트'
        );

        if (element('point_comment_delete', $board) && $this->CI->member->item('mem_id') === abs(element('mem_id', $comment))) {
            $point_delete = 0 - abs(element('point_comment_delete', $board));

            $this->CI->point->insert_point(
                abs(element('mem_id', $comment)),
                $point_delete,
                element('brd_name', $board) . ' ' . $cmt_id . ' 댓글 삭제',
                'comment_delete',
                $cmt_id,
                '댓글 삭제'
            );

        } elseif (element('point_admin_comment_delete', $board) && $this->CI->member->item('mem_id') !== abs(element('mem_id', $comment))) {
            $point_delete = 0 - abs(element('point_admin_comment_delete', $board));

            $this->CI->point->insert_point(
                abs(element('mem_id', $comment)),
                $point_delete,
                element('brd_name', $board) . ' ' . $cmt_id . ' 댓글 삭제',
                'admin_comment_delete',
                $cmt_id,
                '댓글 삭제'
            );
        }

        return true;
    }


    /**
     * 댓글 삭제시 삭제가능한 권한이 있는지 체크합니다
     */
    public function delete_comment_check($cmt_id = 0, $password = '', $realdelete = false)
    {
        $cmt_id = (int) $cmt_id;
        if (empty($cmt_id) OR $cmt_id < 1) {
            $result = array('error' => '올바르지 않은 접근입니다');
            return json_encode($result);
        }

        $this->CI->load->model( array('Post_model', 'Comment_model', 'Comment_meta_model'));

        $comment = $this->CI->Comment_model->get_one($cmt_id);
        $post = $this->CI->Post_model->get_one(element('post_id', $comment));
        $board = $this->CI->board->item_all(element('brd_id', $post));

        if ( ! $this->CI->session->userdata('post_id_' . element('post_id', $post))) {
            $result = array('error' => '해당 게시물에서만 접근 가능합니다');
            return json_encode($result);
        }

        $is_admin = $this->CI->member->is_admin(
            array(
                'board_id' => element('brd_id', $board),
                'group_id' => element('bgr_id', $board),
            )
        );
        $can_delete_comment = false;

        if (element('block_delete', $board) && $is_admin === false) {
            $result = array('error' => '이 게시판의 글은 관리자에 의해서만 삭제가 가능합니다');
            return json_encode($result);
        }

        if ($is_admin === false) {
            $count_comment_reply = $this->CI->Comment_model->count_reply_comment(
                    element('cmt_id', $comment),
                    element('post_id', $comment),
                    element('cmt_num', $comment),
                    element('cmt_reply', $comment)
                );

            if ($count_comment_reply > 0) {
                $result = array('error' => '이 댓글에 답변댓글이 있으므로 댓글을 삭제할 수 없습니다');
                return json_encode($result);
            }
        }

        if (element('protect_comment_day', $board) > 0 && $is_admin === false) {
            if (ctimestamp() - strtotime(element('cmt_datetime', $comment)) >= element('protect_comment_day', $board) * 86400) {
                $result = array('error' => '이 게시판은 ' . element('protect_comment_day', $board) . '일 이상된 댓글의 삭제를 금지합니다');
                return json_encode($result);
            }
        }
        if (element('mem_id', $comment)) {
            if ($is_admin === false && (int) $this->CI->member->item('mem_id') !== abs(element('mem_id', $comment))) {
                $result = array('error' => '회원님은 이 글을 삭제할 권한이 없습니다');
                return json_encode($result);
            }
        } else {

            $this->CI->session->keep_flashdata('can_delete_comment_' . element('cmt_id', $comment));
            if ($is_admin) {
                $this->CI->session->set_flashdata(
                    'can_delete_comment_' . element('cmt_id', $comment),
                    '1'
                );
            }
            if ( ! $this->CI->session->flashdata('can_delete_comment_' . element('cmt_id', $comment)) && $password) {

                if ( ! function_exists('password_hash')) {
                    $this->CI->load->helper('password');
                }
                if (password_verify($password, element('cmt_password', $comment))) {
                    $can_delete_comment = true;
                    $this->CI->session->set_flashdata(
                        'can_delete_comment_' . element('cmt_id', $comment),
                        '1'
                    );
                } else {
                    $result = array('error' => '패스워드가 잘못 입력되었습니다');
                    return json_encode($result);
                }
            }
            if ( ! $this->CI->session->flashdata('can_delete_comment_' . element('cmt_id', $comment)) && $can_delete_comment === false) {

                $result = array('password' => '패스워드가 확인이 필요합니다');
                return json_encode($result);
            }
        }

        if (element('use_comment_delete_log', $board) && $realdelete === false) {
            $updatedata = array(
                'cmt_del' => 1,
            );
            $this->CI->Comment_model->update(element('cmt_id', $comment), $updatedata);
            $metadata = array(
                'delete_mem_id' => $this->CI->member->item('mem_id'),
                'delete_mem_nickname' => $this->CI->member->item('mem_nickname'),
                'delete_datetime' => cdate('Y-m-d H:i:s'),
                'delete_ip' => $this->CI->input->ip_address(),
            );
            $this->CI->Comment_meta_model->save(element('cmt_id', $comment), $metadata);
        } else {
            $this->CI->board->delete_comment($cmt_id);
        }
        $result = array('success' => '댓글이 삭제되었습니다');
        return json_encode($result);
    }


    /**
     * 최근게시물을 가져옵니다
     */
    public function latest($config)
    {
        $view = array();
        $view['view'] = array();

        $this->CI->load->model( array('Board_category_model', 'Post_file_model'));

        $skin = element('skin', $config);
        $brd_id = element('brd_id', $config);
        $brd_key = element('brd_key', $config);
        $exclude_brd_id = element('exclude_brd_id', $config);
        $exclude_brd_key = element('exclude_brd_key', $config);
        $findex = element('findex', $config) ? element('findex', $config) : 'post_id';
        $forder = element('forder', $config) ? element('forder', $config) : 'DESC';
        $limit = element('limit', $config);
        $length = element('length', $config);
        $is_gallery = element('is_gallery', $config);
        $image_width = element('image_width', $config);
        $image_height = element('image_height', $config);
        $period_second = element('period_second', $config);
        $cache_minute = element('cache_minute', $config);

        if ($limit <= 0) {
            return false;
        }

        if ($cache_minute> 0) {
            $cache_brd_id = is_array($brd_id) ? implode('-', $brd_id) : $brd_id;
            $cache_brd_key = is_array($brd_key) ? implode('-', $brd_key) : $brd_key;
            $cache_exclude_brd_id = is_array($exclude_brd_id) ? implode('-', $exclude_brd_id) : $exclude_brd_id;
            $cache_exclude_brd_key = is_array($exclude_brd_key) ? implode('-', $exclude_brd_key) : $exclude_brd_key;
            $cachename = 'latest/latest-s-' . $skin . '-i-' . $cache_brd_id . '-k-' . $cache_brd_key . '-l-' . $cache_exclude_brd_id . '-k-' . $cache_exclude_brd_key . '-l-' . $limit . '-t-' . $length . '-g-' . $is_gallery . '-w-' . $image_width . '-h-' . $image_height . '-p-' . $period_second;
            $html = $this->CI->cache->get($cachename);
            if ($html) {
                return $html;
            }
        }

        if (empty($skin)) {
            $skin = 'basic';
        }
        $view['view']['config'] = $config;
        $view['view']['length'] = $length;
        if ($brd_key) {
            if (is_array($brd_key)) {
                foreach ($brd_key as $v) {
                    $brd_id[] = $this->CI->board->item_key('brd_id', $v);
                }
            } else {
                $brd_id = $this->CI->board->item_key('brd_id', $brd_key);
            }
        }
        if ($exclude_brd_key) {
            if (is_array($exclude_brd_key)) {
                foreach ($exclude_brd_key as $v) {
                    $exclude_brd_id[] = $this->CI->board->item_key('brd_id', $v);
                }
            } else {
                $exclude_brd_id = $this->CI->board->item_key('brd_id', $exclude_brd_key);
            }
        }
        if ($brd_id && ! is_array($brd_id)) {
            $view['view']['board'] = $board= $this->CI->board->item_all($brd_id);
        }
        $where = array();
        $where['post_del'] = 0;
        $where['post_secret'] = 0;

        $this->CI->db->from('post');
        $this->CI->db->where($where);

        if ($brd_id) {
            if (is_array($brd_id)) {
                $this->CI->db->group_start();
                foreach ($brd_id as $v) {
                    $this->CI->db->or_where('brd_id', $v);
                }
                $this->CI->db->group_end();
            } else {
                $this->CI->db->where('brd_id', $brd_id);
            }
        }

        if ($exclude_brd_id) {
            if (is_array($exclude_brd_id)) {
                foreach ($exclude_brd_id as $v) {
                    $this->CI->db->where('brd_id <>', $v);
                }
            } else {
                $this->CI->db->where('brd_id <>', $exclude_brd_id);
            }
        }

        if ($period_second) {
            $post_start_datetime = cdate('Y-m-d H:i:s', ctimestamp() - $period_second);
            $this->CI->db->where('post_datetime >=', $post_start_datetime);
        }

        if ($findex && $forder) {
            $forder = (strtoupper($forder) === 'ASC') ? 'ASC' : 'DESC';
            $this->CI->db->order_by($findex, $forder);
        }
        if (is_numeric($limit)) {
            $this->CI->db->limit($limit);
        }
        $result = $this->CI->db->get();
        $view['view']['latest'] = $latest = $result->result_array();

        $view['view']['latest_limit'] = $limit;
        if ($latest && is_array($latest)) {
            foreach ($latest as $key => $value) {
                $view['view']['latest'][$key]['name'] = display_username(
                    element('post_userid', $value),
                    element('post_nickname', $value)
                );
                $brd_key = $this->CI->board->item_id('brd_key', element('brd_id', $value));
                $view['view']['latest'][$key]['url'] = post_url($brd_key, element('post_id', $value));
                $view['view']['latest'][$key]['title'] = $length ? cut_str(element('post_title', $value), $length) : element('post_title', $value);
                $view['view']['latest'][$key]['display_datetime'] = display_datetime(element('post_datetime', $value), '');
                $view['view']['latest'][$key]['category'] = '';
                if (element('post_category', $value)) {
                        $view['view']['latest'][$key]['category'] = $this->CI->Board_category_model->get_category_info(element('brd_id', $value), element('post_category', $value));
                }
                if ($is_gallery) {
                    if (element('post_image', $value)) {
                        $imagewhere = array(
                            'post_id' => element('post_id', $value),
                            'pfi_is_image' => 1,
                        );
                        $file = $this->CI->Post_file_model->get_one('', '', $imagewhere, '', '', 'pfi_id', 'ASC');
                        if (element('pfi_filename', $file)) {
                            $view['view']['latest'][$key]['thumb_url'] = thumb_url('post', element('pfi_filename', $file), $image_width, $image_height);
                        }
                    } else {
                        $thumb_url = get_post_image_url(element('post_content', $value), $image_width, $image_height);
                        $view['view']['latest'][$key]['thumb_url'] = $thumb_url ? $thumb_url : thumb_url('', '', $image_width, $image_height);
                    }
                }
            }
        }
        $view['view']['skinurl'] = base_url( VIEW_DIR . 'latest/' . $skin);
        $html = $this->CI->load->view('latest/' . $skin . '/latest', $view, true);

        if ($cache_minute> 0) {
            check_cache_dir('latest');
            $this->CI->cache->save($cachename, $html, $cache_minute);
        }

        return $html;
    }

    /**
     * 최근게시물을 가져옵니다
     */
    public function data($config)
    {
        $view = array();
        $view['view'] = array();

        $this->CI->load->model( array('Board_category_model', 'Post_file_model'));

        
        $brd_id = element('brd_id', $config);
        $brd_key = element('brd_key', $config);
        $exclude_brd_id = element('exclude_brd_id', $config);
        $exclude_brd_key = element('exclude_brd_key', $config);
        $findex = element('findex', $config) ? element('findex', $config) : 'post_id';
        $forder = element('forder', $config) ? element('forder', $config) : 'DESC';
        $limit = element('limit', $config);
        $length = element('length', $config);
        $is_gallery = element('is_gallery', $config);
        $image_width = element('image_width', $config);
        $image_height = element('image_height', $config);
        $period_second = element('period_second', $config);
        $cache_minute = element('cache_minute', $config);
        $post_notice = element('post_notice', $config,0);

        if ($limit <= 0) {
            return false;
        }

        
        $view['view']['config'] = $config;
        $view['view']['length'] = $length;
        if($brd_key==="attendance"){
            $this->CI->load->model('Attendance_model');
            $findex = $this->CI->Attendance_model->primary_key;
            $forder = $this->CI->cbconfig->item('attendance_order') === 'desc' ? 'desc' : 'asc';

            /**
             * 게시판 목록에 필요한 정보를 가져옵니다.
             */
            
            $date = cdate('Y-m-d');
            
            if (strlen($date) !== 10) {
                $date = cdate('Y-m-d');
            }
            $arr = explode('-', $date);
            if (checkdate(element(1, $arr), element(2, $arr), element(0, $arr)) === false) {
                $date = cdate('Y-m-d');
            }

            $where = array(
                'att_date' => $date,
            );
            $result = $this->CI->Attendance_model
                ->get_attend_list(3,'', $where, $findex, $forder);

            if (element('list', $result)) {
                foreach (element('list', $result) as $key => $val) {
                    $view['view']['latest'][$key]['url'] = base_url('/attendance');
                    $view['view']['latest'][$key]['title'] = $length ? cut_str(element('att_memo', $val), $length) : element('att_memo', $value);
                    $view['view']['latest'][$key]['display_name'] = display_username(
                        element('mem_userid', $val),
                        element('mem_nickname', $val),'','N'
                    );
                    $view['view']['latest'][$key]['display_datetime'] = display_datetime(
                        element('att_datetime', $val)
                    );
                }
            }
            
        }elseif($brd_key==="notice"){
            $this->CI->load->model('Faq_model');
            $this->CI->load->model('Faq_group_model');

            $where = array(
                'fgr_key' => $brd_key,
            );
            $faqgroup = $this->CI->Faq_group_model->get_one('', '', $where);

            if ( ! element('fgr_id', $faqgroup)) {
                show_404();
            }


            $findex = 'faq_order';
            $forder = 'asc';

            $this->CI->Faq_model->allow_order_field = array('faq_order'); // 정렬이 가능한 필드

            $where = array(
                'fgr_id' => element('fgr_id', $faqgroup),
            );
            $result = $this->CI->Faq_model
                ->get_list('', '', $where, '', $findex, $forder);

            /**
             * 게시판 목록에 필요한 정보를 가져옵니다.
             */
            
            

            if (element('list', $result)) {
                foreach (element('list', $result) as $key => $val) {

                    $content = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? (element('faq_mobile_content', $val) ? element('faq_mobile_content', $val)
                        : element('faq_content', $val)) : element('faq_content', $val);

                    $thumb_width = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? $this->CI->cbconfig->item('faq_mobile_thumb_width')
                        : $this->CI->cbconfig->item('faq_thumb_width');

                    $autolink = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? $this->CI->cbconfig->item('use_faq_mobile_auto_url')
                        : $this->CI->cbconfig->item('use_faq_auto_url');

                    $popup = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? $this->CI->cbconfig->item('faq_mobile_content_target_blank')
                        : $this->CI->cbconfig->item('faq_content_target_blank');

                    $view['view']['latest'][$key]['title'] = display_html_content(
                        element('faq_title', $val),
                        element('faq_content_html_type', $val),
                        $thumb_width,
                        $autolink,
                        $popup,
                        $writer_is_admin = true
                    );

                    $view['view']['latest'][$key]['url'] = base_url('/faq/notice/'.element('faq_id', $val));
                    $view['view']['latest'][$key]['display_name'] = '관리자';
                    $view['view']['latest'][$key]['display_datetime'] = display_datetime(element('faq_datetime', $val));
                    $view['view']['latest'][$key]['content'] = display_html_content(
                        $content,
                        element('faq_content_html_type', $val),
                        $thumb_width,
                        $autolink,
                        $popup,
                        $writer_is_admin = true
                    );
                }
            }
            
        }elseif($brd_key==="faq"){
            $this->CI->load->model('Faq_model');
            $this->CI->load->model('Faq_group_model');

            $where = array(
                'fgr_key' => $brd_key,
            );
            $faqgroup = $this->CI->Faq_group_model->get_one('', '', $where);

            if ( ! element('fgr_id', $faqgroup)) {
                show_404();
            }


            $findex = 'faq_order';
            $forder = 'asc';

            $this->CI->Faq_model->allow_order_field = array('faq_order'); // 정렬이 가능한 필드

            $where = array(
                'fgr_id' => element('fgr_id', $faqgroup),
            );
            $result = $this->CI->Faq_model
                ->get_list('', '', $where, '', $findex, $forder);

            /**
             * 게시판 목록에 필요한 정보를 가져옵니다.
             */
            
            

            if (element('list', $result)) {
                foreach (element('list', $result) as $key => $val) {

                    $content = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? (element('faq_mobile_content', $val) ? element('faq_mobile_content', $val)
                        : element('faq_content', $val)) : element('faq_content', $val);

                    $thumb_width = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? $this->CI->cbconfig->item('faq_mobile_thumb_width')
                        : $this->CI->cbconfig->item('faq_thumb_width');

                    $autolink = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? $this->CI->cbconfig->item('use_faq_mobile_auto_url')
                        : $this->CI->cbconfig->item('use_faq_auto_url');

                    $popup = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? $this->CI->cbconfig->item('faq_mobile_content_target_blank')
                        : $this->CI->cbconfig->item('faq_content_target_blank');

                    $view['view']['latest'][$key]['title'] = display_html_content(
                        element('faq_title', $val),
                        element('faq_content_html_type', $val),
                        $thumb_width,
                        $autolink,
                        $popup,
                        $writer_is_admin = true
                    );

                    $view['view']['latest'][$key]['url'] = base_url('/faq/faq/'.element('faq_id', $val));
                    $view['view']['latest'][$key]['display_name'] = '관리자';
                    $view['view']['latest'][$key]['display_datetime'] = display_datetime(element('faq_datetime', $val));
                    $view['view']['latest'][$key]['content'] = display_html_content(
                        $content,
                        element('faq_content_html_type', $val),
                        $thumb_width,
                        $autolink,
                        $popup,
                        $writer_is_admin = true
                    );
                }
            }
            
        } else {

            if ($brd_key) {
                if (is_array($brd_key)) {
                    foreach ($brd_key as $v) {
                        $brd_id[] = $this->CI->board->item_key('brd_id', $v);
                    }
                } else {
                    $brd_id = $this->CI->board->item_key('brd_id', $brd_key);

                }
            }
            if ($exclude_brd_key) {
                if (is_array($exclude_brd_key)) {
                    foreach ($exclude_brd_key as $v) {
                        $exclude_brd_id[] = $this->CI->board->item_key('brd_id', $v);
                    }
                } else {
                    $exclude_brd_id = $this->CI->board->item_key('brd_id', $exclude_brd_key);
                }
            }
            if ($brd_id && ! is_array($brd_id)) {
                $view['view']['board'] = $board = $this->CI->board->item_all($brd_id);
            }
            $where = array();
            $where['post_del'] = 0;
            $where['post_secret'] = 0;

            $this->CI->db->from('post');
            $this->CI->db->where($where);

            if ($brd_id) {
                if (is_array($brd_id)) {
                    $this->CI->db->group_start();
                    foreach ($brd_id as $v) {
                        $this->CI->db->or_where('brd_id', $v);
                    }
                    $this->CI->db->group_end();
                } else {
                    $this->CI->db->where('brd_id', $brd_id);
                }
            }

            if ($exclude_brd_id) {
                if (is_array($exclude_brd_id)) {
                    foreach ($exclude_brd_id as $v) {
                        $this->CI->db->where('brd_id <>', $v);
                    }
                } else {
                    $this->CI->db->where('brd_id <>', $exclude_brd_id);
                }
            }

            if ($period_second) {
                $post_start_datetime = cdate('Y-m-d H:i:s', ctimestamp() - $period_second);
                $this->CI->db->where('post_datetime >=', $post_start_datetime);
            }

            if($post_notice){            
                $this->CI->db->where('post_notice', $post_notice);
            }
            if ($findex && $forder) {
                $forder = (strtoupper($forder) === 'ASC') ? 'ASC' : 'DESC';
                $this->CI->db->order_by($findex, $forder);
            }
            if (is_numeric($limit)) {
                $this->CI->db->limit($limit);
            }
            $result = $this->CI->db->get();
            
            $latest = $result->result_array();

            if ($latest && is_array($latest)) {
                foreach ($latest as $key => $value) {
                    $view['view']['latest'][$key]['name'] = display_username(
                        element('post_userid', $value),
                        element('post_nickname', $value)
                    );
                    $brd_key = $this->CI->board->item_id('brd_key', element('brd_id', $value));
                    $view['view']['latest'][$key]['url'] = post_url($brd_key, element('post_id', $value));
                    $view['view']['latest'][$key]['title'] = $length ? cut_str(element('post_title', $value), $length) : element('post_title', $value);
                    $view['view']['latest'][$key]['display_datetime'] = display_datetime(element('post_datetime', $value), '');

                    $view['view']['latest'][$key]['display_name'] = display_username(
                                                                        element('post_userid', $value),
                                                                        element('post_nickname', $value));
                    $view['view']['latest'][$key]['display_content'] = cut_str(strip_tags(element('post_content', $value)), 200);

                    $view['view']['latest'][$key]['category'] = '';
                    if (element('post_category', $value)) {
                            $view['view']['latest'][$key]['category'] = $this->CI->Board_category_model->get_category_info(element('brd_id', $value), element('post_category', $value));
                    }

                    $view['view']['latest'][$key]['is_new'] = false;
                    $new_icon_hour = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? element('mobile_new_icon_hour', $board)
                        : element('new_icon_hour', $board);
                    
                    
                    if ($new_icon_hour && ( ctimestamp() - strtotime(element('post_datetime', $value)) <= $new_icon_hour * 3600) && !in_array(element('post_id', $value),explode('||',get_cookie('post_id_cookie')))) {
                        $view['view']['latest'][$key]['is_new'] = true;
                    }

                    if ($is_gallery) {
                        if (element('post_image', $value)) {
                            $imagewhere = array(
                                'post_id' => element('post_id', $value),
                                'pfi_is_image' => 1,
                            );
                            $file = $this->CI->Post_file_model->get_one('', '', $imagewhere, '', '', 'pfi_id', 'ASC');
                            if (element('pfi_filename', $file)) {

                                $view['view']['latest'][$key]['thumb_url'] = thumb_url('post', element('pfi_filename', $file), $image_width, $image_height);
                            }
                        } else {

                            $thumb_url = get_post_image_url(element('post_content', $value), $image_width, $image_height);
                            $view['view']['latest'][$key]['thumb_url'] = $thumb_url ? $thumb_url : thumb_url('', '', $image_width, $image_height);
                        }
                    }
                }
            }
        }
        
        
        return $view;
    }

     /**
     * 최근게시물을 가져옵니다
     */
    public function latest_group($config,$more=0)
    {
        $view = array();
        $view['view'] = array();

        $this->CI->load->model( array('Board_category_model', 'Post_file_model'));

        $skin = element('skin', $config);
        $brd_id = element('brd_id', $config);
        $brd_key = element('brd_key', $config);
        $exclude_brd_id = element('exclude_brd_id', $config);
        $exclude_brd_key = element('exclude_brd_key', $config);
        $findex = element('findex', $config) ? element('findex', $config) : 'post_id';
        $forder = element('forder', $config) ? element('forder', $config) : 'DESC';
        $limit = element('limit', $config);
        $length = element('length', $config);
        $is_gallery = element('is_gallery', $config);
        $image_width = element('image_width', $config);
        $image_height = element('image_height', $config);
        $period_second = element('period_second', $config);
        $cache_minute = element('cache_minute', $config);
        $post_notice = element('post_notice', $config);

        if ($limit <= 0) {
            return false;
        }

        if ($cache_minute> 0) {
            $cache_brd_id = is_array($brd_id) ? implode('-', $brd_id) : $brd_id;
            $cache_brd_key = is_array($brd_key) ? implode('-', $brd_key) : $brd_key;
            $cache_exclude_brd_id = is_array($exclude_brd_id) ? implode('-', $exclude_brd_id) : $exclude_brd_id;
            $cache_exclude_brd_key = is_array($exclude_brd_key) ? implode('-', $exclude_brd_key) : $exclude_brd_key;
            $cachename = 'latest/latest-s-' . $skin . '-i-' . $cache_brd_id . '-k-' . $cache_brd_key . '-l-' . $cache_exclude_brd_id . '-k-' . $cache_exclude_brd_key . '-l-' . $limit . '-t-' . $length . '-g-' . $is_gallery . '-w-' . $image_width . '-h-' . $image_height . '-p-' . $period_second;
            $html = $this->CI->cache->get($cachename);
            if ($html) {
                return $html;
            }
        }

        if (empty($skin)) {
            $skin = 'basic';
        }
        $view['view']['write_text'] = '글 쓰 기';    
        
        
        $view['view']['config'] = $config;
        $view['view']['length'] = $length;

        if($brd_key==="attendance"){
            $view['view']['board']['brd_key']="attendance";
            $view['view']['write_text'] = '출석체크하러가기';    
            $this->CI->load->model('Attendance_model');
            $findex = $this->CI->Attendance_model->primary_key;
            $forder = $this->CI->cbconfig->item('attendance_order') === 'desc' ? 'desc' : 'asc';

            /**
             * 게시판 목록에 필요한 정보를 가져옵니다.
             */
            
            $date = cdate('Y-m-d');
            
            if (strlen($date) !== 10) {
                $date = cdate('Y-m-d');
            }
            $arr = explode('-', $date);
            if (checkdate(element(1, $arr), element(2, $arr), element(0, $arr)) === false) {
                $date = cdate('Y-m-d');
            }

            $where = array(
                'att_date' => $date,
            );

            

            $result = $this->CI->Attendance_model
                ->get_attend_list(3,'', $where, $findex, $forder);

            if (element('list', $result)) {
                foreach (element('list', $result) as $key => $val) {
                    $view['view']['latest'][$key]['url'] = base_url('/attendance');
                    $view['view']['latest'][$key]['title'] = $length ? cut_str(element('att_memo', $val), $length) : element('att_memo', $value);
                    $view['view']['latest'][$key]['display_name'] = display_username(
                        element('mem_userid', $val),
                        element('mem_nickname', $val)
                    );
                    $view['view']['latest'][$key]['display_datetime'] = display_datetime(
                        element('att_datetime', $val)
                    );
                }
            }
            $view['view']['write_url'] = base_url('/attendance');

            $per_page=$limit;
        } else {

            if ($brd_key) {
                if (is_array($brd_key)) {
                    foreach ($brd_key as $v) {
                        $brd_id[] = $this->CI->board->item_key('brd_id', $v);
                    }
                } else {
                    $brd_id = $this->CI->board->item_key('brd_id', $brd_key);
                }
            }
            if ($exclude_brd_key) {
                if (is_array($exclude_brd_key)) {
                    foreach ($exclude_brd_key as $v) {
                        $exclude_brd_id[] = $this->CI->board->item_key('brd_id', $v);
                    }
                } else {
                    $exclude_brd_id = $this->CI->board->item_key('brd_id', $exclude_brd_key);
                }
            }
            if ($brd_id && ! is_array($brd_id)) {
                $view['view']['board'] = $board = $this->CI->board->item_all($brd_id);
            }

            $this->CI->allow_search_field = array('post_id','post_title', 'post_content', 'post_both', 'post_nickname'); // 검색이 가능한 필드
            $this->CI->search_field_equal = array('post_id'); // 검색중 like 가 아닌 = 검색을 하는 필드

            $where = array();
            $where['post_del'] = 0;
            $where['post_secret'] = 0;
            if($post_notice){
                $where['post_notice']=$post_notice;
            }
            $sfield =  $this->CI->input->post('sfield', null, '');
            if ($sfield === 'post_both') {
                $sfield = array('post_title', 'post_content');
            }
            $skeyword = $this->CI->input->post('skeyword', null, '');
            
            if (empty($sfield)) {
                $sfield = array('post_title', 'post_content');
            }


            $page = ((int) $more > 1) ? ((int) $more) : 1;
            

            
            $per_page=$limit;
            $offset = ($page - 1) * $per_page;


            $search_where = array();
            $search_like = array();
            $search_or_like = array();
            if ($sfield && is_array($sfield)) {

                foreach ($sfield as $skey => $sval) {
                    $ssf = $sval;
                    
                    if ($skeyword && $ssf && in_array($ssf, $this->CI->allow_search_field)) {
                        if (in_array($ssf, $this->CI->search_field_equal)) {
                            
                            $search_where[$ssf] = $skeyword;
                        } else {
                            
                            $swordarray = explode(' ', $skeyword);
                            foreach ($swordarray as $str) {
                                if (empty($ssf)) {
                                    continue;
                                }
                                    $search_or_like[] = array($ssf => $str);
                                
                            }
                        }
                    }
                }
            } else {
                $ssf = $sfield;
                if ($skeyword && $ssf && in_array($ssf, $this->CI->allow_search_field)) {
                    if (in_array($ssf, $this->CI->search_field_equal)) {
                        $search_where[$ssf] = $skeyword;
                    } else {
                        $swordarray = explode(' ', $skeyword);
                        foreach ($swordarray as $str) {
                            if (empty($ssf)) {
                                continue;
                            }
                            
                                $search_or_like[] = array($ssf => $str);
                            
                        }
                    }
                }
            }

            if ($search_like) {
                foreach ($search_like as $item) {
                    foreach ($item as $skey => $sval) {
                        $this->CI->db->like($skey, $sval);
                    }
                }
            }
            if ($search_or_like) {
                $this->CI->db->group_start();
                foreach ($search_or_like as $item) {
                    foreach ($item as $skey => $sval) {
                        $this->CI->db->or_like($skey, $sval);
                    }
                }
                $this->CI->db->group_end();
            }
            $this->CI->db->from('post');
            
            $this->CI->db->where($where);

            
            if ($brd_id) {
                if (is_array($brd_id)) {
                    $this->CI->db->group_start();
                    foreach ($brd_id as $v) {
                        $this->CI->db->or_where('brd_id', $v);
                    }
                    $this->CI->db->group_end();
                } else {
                    $this->CI->db->where('brd_id', $brd_id);
                }
            }

            if ($exclude_brd_id) {
                if (is_array($exclude_brd_id)) {
                    foreach ($exclude_brd_id as $v) {
                        $this->CI->db->where('brd_id <>', $v);
                    }
                } else {
                    $this->CI->db->where('brd_id <>', $exclude_brd_id);
                }
            }

            if ($period_second) {
                $post_start_datetime = cdate('Y-m-d H:i:s', ctimestamp() - $period_second);
                $this->CI->db->where('post_datetime >=', $post_start_datetime);
            }

            if ($findex && $forder) {
                $forder = (strtoupper($forder) === 'ASC') ? 'ASC' : 'DESC';
                $this->CI->db->order_by($findex, $forder);
            }

            

            if (is_numeric($limit)) {
                $this->CI->db->limit($limit,$offset);
            }
            $result = $this->CI->db->get();
            $view['view']['latest'] = $latest = $result->result_array();
            
            $view['view']['latest_limit'] = $limit;
            if ($latest && is_array($latest)) {
                foreach ($latest as $key => $value) {
                    $brd_key = $this->CI->board->item_id('brd_key', element('brd_id', $value));
                    $view['view']['latest'][$key]['url'] = post_url($brd_key, element('post_id', $value));
                    $view['view']['latest'][$key]['title'] = $length ? cut_str(element('post_title', $value), $length) : element('post_title', $value);
                    $view['view']['latest'][$key]['display_datetime'] = display_datetime(element('post_datetime', $value), '');
                    $view['view']['latest'][$key]['display_name'] = display_username(
                                                                            element('post_userid', $value),
                                                                            element('post_nickname', $value));
                    $view['view']['latest'][$key]['category'] = '';

                    $view['view']['latest'][$key]['display_content'] = cut_str(strip_tags(element('post_content', $value)), 200);

                    if (element('post_category', $value)) {
                            $view['view']['latest'][$key]['category'] = $this->CI->Board_category_model->get_category_info(element('brd_id', $value), element('post_category', $value));
                    }

                    $view['view']['latest'][$key]['is_new'] = false;
                    $new_icon_hour = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? element('mobile_new_icon_hour', $board)
                        : element('new_icon_hour', $board);

                    if ($new_icon_hour && ( ctimestamp() - strtotime(element('post_datetime', $value)) <= $new_icon_hour * 3600) && !in_array(element('post_id', $value),explode('||',get_cookie('post_id_cookie')))) {
                        $view['view']['latest'][$key]['is_new'] = true;
                    }

                    if ($is_gallery) {
                        if (element('post_image', $value)) {
                            $imagewhere = array(
                                'post_id' => element('post_id', $value),
                                'pfi_is_image' => 1,
                            );
                            $file = $this->CI->Post_file_model->get_one('', '', $imagewhere, '', '', 'pfi_id', 'ASC');
                            if (element('pfi_filename', $file)) {
                                $view['view']['latest'][$key]['thumb_url'] = thumb_url('post', element('pfi_filename', $file), $image_width, $image_height);
                            }
                        } else {
                            $thumb_url = get_post_image_url(element('post_content', $value), $image_width, $image_height);
                            $view['view']['latest'][$key]['thumb_url'] = $thumb_url ? $thumb_url : thumb_url('', '', $image_width, $image_height);
                        }
                    }
                }
            }

            $alertmessage = $this->CI->member->is_member()
                ? '회원님은 내용을 볼 수 있는 권한이 없습니다'
                : '비회원은 내용을 볼 수 있는 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오';

            $check = array(
                'group_id' => element('bgr_id', $board),
                'board_id' => element('brd_id', $board),
            );

            $this->CI->load->library('accesslevel');
            $can_write = $this->CI->accesslevel->is_accessable(
                element('access_write', $board),
                element('access_write_level', $board),
                element('access_write_group', $board),
                $check
            );


            $this->CI->load->model('Member_group_model');

            $groupwhere = array(
                'mgr_order' => element('access_write_level', $board),
            );

            
            $mgr_title = $this->CI->Member_group_model->get_one('', 'mgr_title',$groupwhere);


            $view['view']['write_url'] = '';
            if ($can_write === true) {

                $view['view']['write_url'] = write_url($brd_key);
            } elseif ($this->CI->cbconfig->get_device_view_type() !== 'mobile' && element('always_show_write_button', $board)) {
                if($this->CI->member->is_member()) $view['view']['write_url'] = 'javascript:alert(\'글쓰기 권한이 없습니다.\\n\\n'.$mgr_title['mgr_title'].' 이상 권한이 필요합니다.\');';
                else $view['view']['write_url'] = 'javascript:alert(\'비회원은 글쓰기 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.\');';
            } elseif ($this->CI->cbconfig->get_device_view_type() === 'mobile' && element('mobile_always_show_write_button', $board)) {
                if($this->CI->member->is_member()) $view['view']['write_url'] = 'javascript:alert(\'글쓰기 권한이 없습니다.\\n\\n'.$mgr_title['mgr_title'].' 이상 권한이 필요합니다.\');';
                else $view['view']['write_url'] = 'javascript:alert(\'비회원은 글쓰기 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.\');';
                
            }
        }

        
        
        if($brd_key==="attendance"){
            

            /**
             * 게시판 목록에 필요한 정보를 가져옵니다.
             */
            
            $date = cdate('Y-m-d');
            
            if (strlen($date) !== 10) {
                $date = cdate('Y-m-d');
            }
            $arr = explode('-', $date);
            if (checkdate(element(1, $arr), element(2, $arr), element(0, $arr)) === false) {
                $date = cdate('Y-m-d');
            }

            $where = array(
                'att_date' => $date,
            );

            

            $result = $this->CI->Attendance_model
                ->get_attend_list('','', $where);


            

            $total_rows['rownum'] = element('total_rows',$result);

        } else {
            $this->CI->db->select('count(*) as rownum');
            if ($sfield && is_array($sfield)) {

                foreach ($sfield as $skey => $sval) {
                    $ssf = $sval;
                    
                    if ($skeyword && $ssf && in_array($ssf, $this->CI->allow_search_field)) {
                        if (in_array($ssf, $this->CI->search_field_equal)) {
                            
                            $search_where[$ssf] = $skeyword;
                        } else {
                            
                            $swordarray = explode(' ', $skeyword);
                            foreach ($swordarray as $str) {
                                if (empty($ssf)) {
                                    continue;
                                }
                                    $search_or_like[] = array($ssf => $str);
                                
                            }
                        }
                    }
                }
            } else {
                $ssf = $sfield;
                if ($skeyword && $ssf && in_array($ssf, $this->CI->allow_search_field)) {
                    if (in_array($ssf, $this->CI->search_field_equal)) {
                        $search_where[$ssf] = $skeyword;
                    } else {
                        $swordarray = explode(' ', $skeyword);
                        foreach ($swordarray as $str) {
                            if (empty($ssf)) {
                                continue;
                            }
                            
                                $search_or_like[] = array($ssf => $str);
                            
                        }
                    }
                }
            }

            if ($search_like) {
                foreach ($search_like as $item) {
                    foreach ($item as $skey => $sval) {
                        $this->CI->db->like($skey, $sval);
                    }
                }
            }
            if ($search_or_like) {
                $this->CI->db->group_start();
                foreach ($search_or_like as $item) {
                    foreach ($item as $skey => $sval) {
                        $this->CI->db->or_like($skey, $sval);
                    }
                }
                $this->CI->db->group_end();
            }
        
            $this->CI->db->from('post');
            $this->CI->db->where($where);

            
            if ($brd_id) {
                if (is_array($brd_id)) {
                    $this->CI->db->group_start();
                    foreach ($brd_id as $v) {
                        $this->CI->db->or_where('brd_id', $v);
                    }
                    $this->CI->db->group_end();
                } else {
                    $this->CI->db->where('brd_id', $brd_id);
                }
            }

            if ($exclude_brd_id) {
                if (is_array($exclude_brd_id)) {
                    foreach ($exclude_brd_id as $v) {
                        $this->CI->db->where('brd_id <>', $v);
                    }
                } else {
                    $this->CI->db->where('brd_id <>', $exclude_brd_id);
                }
            }

            if ($period_second) {
                $post_start_datetime = cdate('Y-m-d H:i:s', ctimestamp() - $period_second);
                $this->CI->db->where('post_datetime >=', $post_start_datetime);
            }

            $result = $this->CI->db->get();

            $total_rows = $result->row_array();
        }
        
        
        
        
        $view['view']['page'] = ceil($total_rows['rownum'] / $per_page);
        
        
        

        $view['view']['skinurl'] = base_url( VIEW_DIR . 'group/' . $skin);
        
        $html = $this->CI->load->view('group/' . $skin . '/latest_group', $view, true);

        if ($cache_minute> 0) {
            check_cache_dir('latest');
            $this->CI->cache->save($cachename, $html, $cache_minute);
        }

        return $html;
    }


    public function latest_group_desktop($config,$more=0)
    {
        
        $view = array();
        $view['view'] = array();

        $this->CI->load->model( array('Board_category_model', 'Post_file_model'));

        $skin = element('skin', $config);
        $brd_id = element('brd_id', $config);
        $brd_key = element('brd_key', $config);
        $exclude_brd_id = element('exclude_brd_id', $config);
        $exclude_brd_key = element('exclude_brd_key', $config);
        $findex = element('findex', $config) ? element('findex', $config) : 'post_id';
        $forder = element('forder', $config) ? element('forder', $config) : 'DESC';
        $limit = element('limit', $config);
        $length = element('length', $config);
        $is_gallery = element('is_gallery', $config);
        $image_width = element('image_width', $config);
        $image_height = element('image_height', $config);
        $period_second = element('period_second', $config);
        $cache_minute = element('cache_minute', $config);
        $post_notice = element('post_notice', $config);

        if ($limit <= 0) {
            return false;
        }

        if ($cache_minute> 0) {
            $cache_brd_id = is_array($brd_id) ? implode('-', $brd_id) : $brd_id;
            $cache_brd_key = is_array($brd_key) ? implode('-', $brd_key) : $brd_key;
            $cache_exclude_brd_id = is_array($exclude_brd_id) ? implode('-', $exclude_brd_id) : $exclude_brd_id;
            $cache_exclude_brd_key = is_array($exclude_brd_key) ? implode('-', $exclude_brd_key) : $exclude_brd_key;
            $cachename = 'latest/latest-s-' . $skin . '-i-' . $cache_brd_id . '-k-' . $cache_brd_key . '-l-' . $cache_exclude_brd_id . '-k-' . $cache_exclude_brd_key . '-l-' . $limit . '-t-' . $length . '-g-' . $is_gallery . '-w-' . $image_width . '-h-' . $image_height . '-p-' . $period_second;
            $html = $this->CI->cache->get($cachename);
            if ($html) {
                return $html;
            }
        }

        if (empty($skin)) {
            $skin = 'basic';
        }
        $view['view']['write_text'] = '글 쓰 기';    
        
        
        $view['view']['config'] = $config;
        $view['view']['length'] = $length;

        if($brd_key==="attendance"){
            $view['view']['board']['brd_key']="attendance";
            $view['view']['write_text'] = '출석체크하러가기';    
            $this->CI->load->model('Attendance_model');
            $findex = $this->CI->Attendance_model->primary_key;
            $forder = $this->CI->cbconfig->item('attendance_order') === 'desc' ? 'desc' : 'asc';

            /**
             * 게시판 목록에 필요한 정보를 가져옵니다.
             */
            
            $date = cdate('Y-m-d');
            
            if (strlen($date) !== 10) {
                $date = cdate('Y-m-d');
            }
            $arr = explode('-', $date);
            if (checkdate(element(1, $arr), element(2, $arr), element(0, $arr)) === false) {
                $date = cdate('Y-m-d');
            }

            $where = array(
                'att_date' => $date,
            );

            

            $result = $this->CI->Attendance_model
                ->get_attend_list(3,'', $where, $findex, $forder);

            if (element('list', $result)) {
                foreach (element('list', $result) as $key => $val) {
                    $view['view']['latest'][$key]['url'] = base_url('/attendance');
                    $view['view']['latest'][$key]['title'] = $length ? cut_str(element('att_memo', $val), $length) : element('att_memo', $value);
                    $view['view']['latest'][$key]['display_name'] = display_username(
                        element('mem_userid', $val),
                        element('mem_nickname', $val)
                    );
                    $view['view']['latest'][$key]['display_datetime'] = display_datetime(
                        element('att_datetime', $val)
                    );
                }
            }
            $view['view']['write_url'] = base_url('/attendance');

            $per_page=$limit;
        } else {

            if ($brd_key) {
                if (is_array($brd_key)) {
                    foreach ($brd_key as $v) {
                        $brd_id[] = $this->CI->board->item_key('brd_id', $v);
                    }
                } else {
                    $brd_id = $this->CI->board->item_key('brd_id', $brd_key);
                }
            }
            if ($exclude_brd_key) {
                if (is_array($exclude_brd_key)) {
                    foreach ($exclude_brd_key as $v) {
                        $exclude_brd_id[] = $this->CI->board->item_key('brd_id', $v);
                    }
                } else {
                    $exclude_brd_id = $this->CI->board->item_key('brd_id', $exclude_brd_key);
                }
            }
            if ($brd_id && ! is_array($brd_id)) {
                $view['view']['board'] = $board = $this->CI->board->item_all($brd_id);
            }

            $this->CI->allow_search_field = array('post_id','post_title', 'post_content', 'post_both', 'post_nickname'); // 검색이 가능한 필드
            $this->CI->search_field_equal = array('post_id'); // 검색중 like 가 아닌 = 검색을 하는 필드

            $where = array();
            $where['post_del'] = 0;
            $where['post_secret'] = 0;
            if($post_notice){
                $where['post_notice']=$post_notice;
            }
            $sfield =  $this->CI->input->post('sfield', null, '');
            if ($sfield === 'post_both') {
                $sfield = array('post_title', 'post_content');
            }
            $skeyword = $this->CI->input->post('skeyword', null, '');
            
            if (empty($sfield)) {
                $sfield = array('post_title', 'post_content');
            }


            $page = ((int) $more > 1) ? ((int) $more) : 1;
            

            
            $per_page=$limit;
            $offset = ($page - 1) * $per_page;


            $search_where = array();
            $search_like = array();
            $search_or_like = array();
            if ($sfield && is_array($sfield)) {

                foreach ($sfield as $skey => $sval) {
                    $ssf = $sval;
                    
                    if ($skeyword && $ssf && in_array($ssf, $this->CI->allow_search_field)) {
                        if (in_array($ssf, $this->CI->search_field_equal)) {
                            
                            $search_where[$ssf] = $skeyword;
                        } else {
                            
                            $swordarray = explode(' ', $skeyword);
                            foreach ($swordarray as $str) {
                                if (empty($ssf)) {
                                    continue;
                                }
                                    $search_or_like[] = array($ssf => $str);
                                
                            }
                        }
                    }
                }
            } else {
                $ssf = $sfield;
                if ($skeyword && $ssf && in_array($ssf, $this->CI->allow_search_field)) {
                    if (in_array($ssf, $this->CI->search_field_equal)) {
                        $search_where[$ssf] = $skeyword;
                    } else {
                        $swordarray = explode(' ', $skeyword);
                        foreach ($swordarray as $str) {
                            if (empty($ssf)) {
                                continue;
                            }
                            
                                $search_or_like[] = array($ssf => $str);
                            
                        }
                    }
                }
            }

            if ($search_like) {
                foreach ($search_like as $item) {
                    foreach ($item as $skey => $sval) {
                        $this->CI->db->like($skey, $sval);
                    }
                }
            }
            if ($search_or_like) {
                $this->CI->db->group_start();
                foreach ($search_or_like as $item) {
                    foreach ($item as $skey => $sval) {
                        $this->CI->db->or_like($skey, $sval);
                    }
                }
                $this->CI->db->group_end();
            }
            $this->CI->db->from('post');
            
            $this->CI->db->where($where);

            
            if ($brd_id) {
                if (is_array($brd_id)) {
                    $this->CI->db->group_start();
                    foreach ($brd_id as $v) {
                        $this->CI->db->or_where('brd_id', $v);
                    }
                    $this->CI->db->group_end();
                } else {
                    $this->CI->db->where('brd_id', $brd_id);
                }
            }

            if ($exclude_brd_id) {
                if (is_array($exclude_brd_id)) {
                    foreach ($exclude_brd_id as $v) {
                        $this->CI->db->where('brd_id <>', $v);
                    }
                } else {
                    $this->CI->db->where('brd_id <>', $exclude_brd_id);
                }
            }

            if ($period_second) {
                $post_start_datetime = cdate('Y-m-d H:i:s', ctimestamp() - $period_second);
                $this->CI->db->where('post_datetime >=', $post_start_datetime);
            }

            if ($findex && $forder) {
                $forder = (strtoupper($forder) === 'ASC') ? 'ASC' : 'DESC';
                $this->CI->db->order_by($findex, $forder);
            }

            

            if (is_numeric($limit)) {
                $this->CI->db->limit($limit,$offset);
            }
            $result = $this->CI->db->get();
            $view['view']['latest'] = $latest = $result->result_array();
            
            $view['view']['latest_limit'] = $limit;
            if ($latest && is_array($latest)) {
                foreach ($latest as $key => $value) {
                    $brd_key = $this->CI->board->item_id('brd_key', element('brd_id', $value));
                    $view['view']['latest'][$key]['url'] = post_url($brd_key, element('post_id', $value));
                    $view['view']['latest'][$key]['title'] = $length ? cut_str(element('post_title', $value), $length) : element('post_title', $value);
                    $view['view']['latest'][$key]['display_datetime'] = display_datetime(element('post_datetime', $value), '');
                    $view['view']['latest'][$key]['display_name'] = display_username(
                                                                            element('post_userid', $value),
                                                                            element('post_nickname', $value));
                    $view['view']['latest'][$key]['category'] = '';

                    $view['view']['latest'][$key]['display_content'] = cut_str(strip_tags(element('post_content', $value)), 200);

                    if (element('post_category', $value)) {
                            $view['view']['latest'][$key]['category'] = $this->CI->Board_category_model->get_category_info(element('brd_id', $value), element('post_category', $value));
                    }

                    $view['view']['latest'][$key]['is_new'] = false;
                    $new_icon_hour = ($this->CI->cbconfig->get_device_view_type() === 'mobile')
                        ? element('mobile_new_icon_hour', $board)
                        : element('new_icon_hour', $board);

                    if ($new_icon_hour && ( ctimestamp() - strtotime(element('post_datetime', $value)) <= $new_icon_hour * 3600) && !in_array(element('post_id', $value),explode('||',get_cookie('post_id_cookie')))) {
                        $view['view']['latest'][$key]['is_new'] = true;
                    }

                    if ($is_gallery) {
                        if (element('post_image', $value)) {
                            $imagewhere = array(
                                'post_id' => element('post_id', $value),
                                'pfi_is_image' => 1,
                            );
                            $file = $this->CI->Post_file_model->get_one('', '', $imagewhere, '', '', 'pfi_id', 'ASC');
                            if (element('pfi_filename', $file)) {
                                $view['view']['latest'][$key]['thumb_url'] = thumb_url('post', element('pfi_filename', $file), $image_width, $image_height);
                            }
                        } else {
                            $thumb_url = get_post_image_url(element('post_content', $value), $image_width, $image_height);
                            $view['view']['latest'][$key]['thumb_url'] = $thumb_url ? $thumb_url : thumb_url('', '', $image_width, $image_height);
                        }
                    }
                }
            }

            $alertmessage = $this->CI->member->is_member()
                ? '회원님은 내용을 볼 수 있는 권한이 없습니다'
                : '비회원은 내용을 볼 수 있는 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오';

            $check = array(
                'group_id' => element('bgr_id', $board),
                'board_id' => element('brd_id', $board),
            );

            $this->CI->load->library('accesslevel');
            $can_write = $this->CI->accesslevel->is_accessable(
                element('access_write', $board),
                element('access_write_level', $board),
                element('access_write_group', $board),
                $check
            );


            $this->CI->load->model('Member_group_model');

            $groupwhere = array(
                'mgr_order' => element('access_write_level', $board),
            );

            
            $mgr_title = $this->CI->Member_group_model->get_one('', 'mgr_title',$groupwhere);


            $view['view']['write_url'] = '';
            if ($can_write === true) {

                $view['view']['write_url'] = write_url($brd_key);
            } elseif ($this->CI->cbconfig->get_device_view_type() !== 'mobile' && element('always_show_write_button', $board)) {
                if($this->CI->member->is_member()) $view['view']['write_url'] = 'javascript:alert(\'글쓰기 권한이 없습니다.\\n\\n'.$mgr_title['mgr_title'].' 이상 권한이 필요합니다.\');';
                else $view['view']['write_url'] = 'javascript:alert(\'비회원은 글쓰기 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.\');';
            } elseif ($this->CI->cbconfig->get_device_view_type() === 'mobile' && element('mobile_always_show_write_button', $board)) {
                if($this->CI->member->is_member()) $view['view']['write_url'] = 'javascript:alert(\'글쓰기 권한이 없습니다.\\n\\n'.$mgr_title['mgr_title'].' 이상 권한이 필요합니다.\');';
                else $view['view']['write_url'] = 'javascript:alert(\'비회원은 글쓰기 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.\');';
                
            }
        }

        
        
        if($brd_key==="attendance"){
            

            /**
             * 게시판 목록에 필요한 정보를 가져옵니다.
             */
            
            $date = cdate('Y-m-d');
            
            if (strlen($date) !== 10) {
                $date = cdate('Y-m-d');
            }
            $arr = explode('-', $date);
            if (checkdate(element(1, $arr), element(2, $arr), element(0, $arr)) === false) {
                $date = cdate('Y-m-d');
            }

            $where = array(
                'att_date' => $date,
            );

            

            $result = $this->CI->Attendance_model
                ->get_attend_list('','', $where);


            

            $total_rows['rownum'] = element('total_rows',$result);

        } else {
            $this->CI->db->select('count(*) as rownum');
            if ($sfield && is_array($sfield)) {

                foreach ($sfield as $skey => $sval) {
                    $ssf = $sval;
                    
                    if ($skeyword && $ssf && in_array($ssf, $this->CI->allow_search_field)) {
                        if (in_array($ssf, $this->CI->search_field_equal)) {
                            
                            $search_where[$ssf] = $skeyword;
                        } else {
                            
                            $swordarray = explode(' ', $skeyword);
                            foreach ($swordarray as $str) {
                                if (empty($ssf)) {
                                    continue;
                                }
                                    $search_or_like[] = array($ssf => $str);
                                
                            }
                        }
                    }
                }
            } else {
                $ssf = $sfield;
                if ($skeyword && $ssf && in_array($ssf, $this->CI->allow_search_field)) {
                    if (in_array($ssf, $this->CI->search_field_equal)) {
                        $search_where[$ssf] = $skeyword;
                    } else {
                        $swordarray = explode(' ', $skeyword);
                        foreach ($swordarray as $str) {
                            if (empty($ssf)) {
                                continue;
                            }
                            
                                $search_or_like[] = array($ssf => $str);
                            
                        }
                    }
                }
            }

            if ($search_like) {
                foreach ($search_like as $item) {
                    foreach ($item as $skey => $sval) {
                        $this->CI->db->like($skey, $sval);
                    }
                }
            }
            if ($search_or_like) {
                $this->CI->db->group_start();
                foreach ($search_or_like as $item) {
                    foreach ($item as $skey => $sval) {
                        $this->CI->db->or_like($skey, $sval);
                    }
                }
                $this->CI->db->group_end();
            }
        
            $this->CI->db->from('post');
            $this->CI->db->where($where);

            
            if ($brd_id) {
                if (is_array($brd_id)) {
                    $this->CI->db->group_start();
                    foreach ($brd_id as $v) {
                        $this->CI->db->or_where('brd_id', $v);
                    }
                    $this->CI->db->group_end();
                } else {
                    $this->CI->db->where('brd_id', $brd_id);
                }
            }

            if ($exclude_brd_id) {
                if (is_array($exclude_brd_id)) {
                    foreach ($exclude_brd_id as $v) {
                        $this->CI->db->where('brd_id <>', $v);
                    }
                } else {
                    $this->CI->db->where('brd_id <>', $exclude_brd_id);
                }
            }

            if ($period_second) {
                $post_start_datetime = cdate('Y-m-d H:i:s', ctimestamp() - $period_second);
                $this->CI->db->where('post_datetime >=', $post_start_datetime);
            }

            $result = $this->CI->db->get();

            $total_rows = $result->row_array();
        }
        
        
        
        
        $view['view']['page'] = ceil($total_rows['rownum'] / $per_page);
        
        
        

        $view['view']['skinurl'] = base_url( VIEW_DIR . 'group/' . $skin);
        if($more) $html = $this->CI->load->view('group/' . $skin . '/latest_group_more', $view, true);
        else $html = $this->CI->load->view('group/' . $skin . '/latest_group', $view, true);

        if ($cache_minute> 0) {
            check_cache_dir('latest');
            $this->CI->cache->save($cachename, $html, $cache_minute);
        }

        return $html;
    }

    /**
     * 최근 댓글을 가져옵니다
     */
    public function latest_comment($config)
    {
        $view = array();
        $view['view'] = array();

        $this->CI->load->model( array('Comment_model'));

        $skin = element('skin', $config);
        $brd_id = element('brd_id', $config);
        $brd_key = element('brd_key', $config);
        $exclude_brd_id = element('exclude_brd_id', $config);
        $exclude_brd_key = element('exclude_brd_key', $config);
        $findex = element('findex', $config) ? element('findex', $config) : 'cmt_id';
        $forder = element('forder', $config) ? element('forder', $config) : 'DESC';
        $limit = element('limit', $config);
        $length = element('length', $config);
        $period_second = element('period_second', $config);
        $cache_minute = element('cache_minute', $config);

        if ($limit <= 0) {
            return false;
        }

        if ($cache_minute> 0) {
            $cache_brd_id = is_array($brd_id) ? implode('-', $brd_id) : $brd_id;
            $cache_brd_key = is_array($brd_key) ? implode('-', $brd_key) : $brd_key;
            $cache_exclude_brd_id = is_array($exclude_brd_id) ? implode('-', $exclude_brd_id) : $exclude_brd_id;
            $cache_exclude_brd_key = is_array($exclude_brd_key) ? implode('-', $exclude_brd_key) : $exclude_brd_key;
            $cachename = 'latest_comment/latest-comment-s-' . $skin . '-i-' . $cache_brd_id . '-k-' . $cache_brd_key . '-l-' . $cache_exclude_brd_id . '-k-' . $cache_exclude_brd_key . '-l-' . $limit . '-t-' . $length . '-p-' . $period_second;
            $html = $this->CI->cache->get($cachename);
            if ($html) {
                return $html;
            }
        }

        if (empty($skin)) {
            $skin = 'basic';
        }
        $view['view']['config'] = $config;
        $view['view']['length'] = $length;
        if ($brd_key) {
            if (is_array($brd_key)) {
                foreach ($brd_key as $v) {
                    $brd_id[] = $this->CI->board->item_key('brd_id', $v);
                }
            } else {
                $brd_id = $this->CI->board->item_key('brd_id', $brd_key);
            }
        }
        if ($exclude_brd_key) {
            if (is_array($exclude_brd_key)) {
                foreach ($exclude_brd_key as $v) {
                    $exclude_brd_id[] = $this->CI->board->item_key('brd_id', $v);
                }
            } else {
                $exclude_brd_id = $this->CI->board->item_key('brd_id', $exclude_brd_key);
            }
        }
        if ($brd_id && ! is_array($brd_id)) {
            $view['view']['board'] = $this->CI->board->item_all($brd_id);
        }
        $where = array();
        $where['cmt_del'] = 0;
        $where['cmt_secret'] = 0;
        $where['post_secret'] = 0;
        $where['post_del'] = 0;

        $this->CI->db->from('comment');
        $this->CI->db->join('post', 'post.post_id=comment.post_id', 'inner');
        $this->CI->db->where($where);

        if ($brd_id) {
            if (is_array($brd_id)) {
                $this->CI->db->group_start();
                foreach ($brd_id as $v) {
                    $this->CI->db->or_where('comment.brd_id', $v);
                }
                $this->CI->db->group_end();
            } else {
                $this->CI->db->where('comment.brd_id', $brd_id);
            }
        }

        if ($exclude_brd_id) {
            if (is_array($exclude_brd_id)) {
                foreach ($exclude_brd_id as $v) {
                    $this->CI->db->where('comment.brd_id <>', $v);
                }
            } else {
                $this->CI->db->where('comment.brd_id <>', $exclude_brd_id);
            }
        }

        if ($period_second) {
            $comment_start_datetime = cdate('Y-m-d H:i:s', ctimestamp() - $period_second);
            $this->CI->db->where('cmt_datetime >=', $comment_start_datetime);
        }

        if ($findex && $forder) {
            $forder = (strtoupper($forder) === 'ASC') ? 'ASC' : 'DESC';
            $this->CI->db->order_by($findex, $forder);
        }
        if (is_numeric($limit)) {
            $this->CI->db->limit($limit);
        }
        $result = $this->CI->db->get();
        $view['view']['latest'] = $latest = $result->result_array();

        $view['view']['latest_limit'] = $limit;
        if ($latest && is_array($latest)) {
            foreach ($latest as $key => $value) {
                $view['view']['latest'][$key]['name'] = display_username(
                    element('cmt_userid', $value),
                    element('cmt_nickname', $value)
                );
                $brd_key = $this->CI->board->item_id('brd_key', element('brd_id', $value));
                $view['view']['latest'][$key]['url'] = post_url($brd_key, element('post_id', $value)) . '#comment_' . element('cmt_id', $value);
                $view['view']['latest'][$key]['title'] = $length ? cut_str(element('cmt_content', $value), $length) : element('cmt_content', $value);
                $view['view']['latest'][$key]['display_datetime'] = display_datetime(element('cmt_datetime', $value), '');
            }
        }
        $view['view']['skinurl'] = base_url( VIEW_DIR . 'latest/' . $skin);
        $html = $this->CI->load->view('latest/' . $skin . '/latest', $view, true);

        if ($cache_minute> 0) {
            check_cache_dir('latest_comment');
            $this->CI->cache->save($cachename, $html, $cache_minute);
        }

        return $html;
    }


    /**
     * 인기태그를 가져옵니다
     */
    public function get_popular_tags($start_date = '', $limit = '')
    {
        $cachename = 'latest/get_popular_tags_' . $start_date . '_' . $limit;
        $data = array();

        if ( ! $data = $this->CI->cache->get($cachename)) {

            $this->CI->load->model( array('Post_tag_model'));
            $result = $this->CI->Post_tag_model->get_popular_tags($start_date, $limit);

            $data['result'] = $result;
            $data['cached'] = '1';
            check_cache_dir('latest');
            $this->CI->cache->save($cachename, $data, 60);

        }
        return isset($data['result']) ? $data['result'] : false;
    }


    /**
     * 어드민인지 체크합니다
     */
    public function is_admin($brd_id = 0)
    {
        $brd_id = (int) $brd_id;
        if (empty($brd_id) OR $brd_id < 1) {
            return false;
        }

        if ( ! $this->CI->member->item('mem_id')) {
            return false;
        }
        if ($this->call_admin) {
            return $this->admin;
        }
        $this->call_admin = true;
        $countwhere = array(
            'brd_id' => $brd_id,
            'mem_id' => $this->CI->member->item('mem_id'),
        );
        $this->CI->load->model('Board_admin_model');
        $count = $this->CI->Board_admin_model->count_by($countwhere);
        if ($count) {
            $this->admin = true;
        } else {
            $this->admin = false;
        }
        return $this->admin;
    }
}
