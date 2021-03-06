<?php $this->managelayout->add_css(element('view_skin_url', $layout) . '/css/style.css'); ?>

<article class="wrap01">
    <section class="attend_check main_title">
        <h2>출 석 체 크</h2>

        <div class="group wrapmemo">
            <?php
            $attributes = array('class' => 'attendance_box /*text-center mb20*/', 'name' => 'attendanceform', 'id' => 'attendanceform');
            echo form_open('', $attributes);
            ?>

            <label>한마디</label>

            <input type="text" name="memo" value="<?php echo html_escape(element(0, element('default_memo', $view))); ?>" id="att_memo" 
            /*class="input"*/ onClick="this.value='';" />

            <button type="button" name="change_memo" /*class="btn btn-default"*/ id="change_memo"><span class="fa fa-refresh"></span></button>

            <button type="button" name="submit" class="/*btn btn-success*/ middle_font" id="add_attendance">출 첵 하 기</button>

            <button type="button" name="view_policy" class="view_policy view_point" /*pull-right btn-xs  btn btn-default*/>포 인 트 정 책 보 기</button>
            <?php echo form_close(); ?>

            <div class="alert-point-policy alert /*alert-dismissible alert-warning*/">
                <button type="button" class="view_point"><!-- &times; --> x</button>
                <strong>포인트 정책</strong>
                <br/>
                <br/>
                출석가능시간 : <?php echo $this->cbconfig->item('attendance_start_time'); ?> ~ <?php echo $this->cbconfig->item('attendance_end_time'); ?><br />
                <?php
                if ($this->cbconfig->item('attendance_point')) {
                    echo '출석포인트 : ' . $this->cbconfig->item('attendance_point') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_1')) {
                    echo '1등포인트 : 출석포인트 + ' . $this->cbconfig->item('attendance_point_1') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_2')) {
                    echo '2등포인트 : 출석포인트 + ' . $this->cbconfig->item('attendance_point_2') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_3')) {
                    echo '3등포인트 : 출석포인트 + ' . $this->cbconfig->item('attendance_point_3') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_4')) {
                    echo '4등포인트 : 출석포인트 + ' . $this->cbconfig->item('attendance_point_4') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_5')) {
                    echo '5등포인트 : 출석포인트 + ' . $this->cbconfig->item('attendance_point_5') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_6')) {
                    echo '6등포인트 : 출석포인트 + ' . $this->cbconfig->item('attendance_point_6') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_7')) {
                    echo '7등포인트 : 출석포인트 + ' . $this->cbconfig->item('attendance_point_7') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_8')) {
                    echo '8등포인트 : 출석포인트 + ' . $this->cbconfig->item('attendance_point_8') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_9')) {
                    echo '9등포인트 : 출석포인트 + ' . $this->cbconfig->item('attendance_point_9') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_10')) {
                    echo '10등포인트 : 출석포인트 + ' . $this->cbconfig->item('attendance_point_10') . '점<br />';
                }
                if ($this->cbconfig->item('attendance_point_regular') && $this->cbconfig->item('attendance_point_regular_days')) {
                    echo '개근포인트 : ' . $this->cbconfig->item('attendance_point_regular') . '점, ' . $this->cbconfig->item('attendance_point_regular_days') . '일 마다 지급<br />';
                }
                ?>
            </div>
        </div>

        <div class="selected-date big_font"><?php echo element('date_format', $view); ?></div>
            <table class="middle_font">
                <tr>
                    <td><a href="<?php echo site_url('attendance?date=' . element('lastmonth', $view)); ?>">◀ 지난달</a></td>
                    <td><a href="<?php echo site_url('attendance'); ?>">오늘보기</a></td>
                    <td><a href="<?php echo element('nextmonth', $view) ? site_url('attendance?date=' . element('nextmonth', $view)) : 'javascript:;'; ?>">다음달 ▶</a></td>
                </tr>
            </table>
            <ul class="date-navigation">
                   <!--  <li><a href="<?php echo site_url('attendance'); ?>">오늘보기</a></li>
                   <li><a href="<?php echo site_url('attendance?date=' . element('lastmonth', $view)); ?>">지난달</a></li> -->
                   <?php
                   for ($day = 1; $day <= element('lastday', $view); $day++) {
                    ?>
                    <li class="datepick <?php echo (sprintf("%02d", $day) === element('d', $view)) ? ' active' : ''; ?>" data-attendance-date="<?php echo element('ym', $view) . "-" . sprintf("%02d", $day);?>"><?php echo $day; ?></li>
                    <?php
                }
                ?>
                <!--  <li><a href="<?php echo element('nextmonth', $view) ? site_url('attendance?date=' . element('nextmonth', $view)) : 'javascript:;'; ?>">다음달</a></li> -->
            </ul>

    <div id="viewattendance"></div>



 
    </section>
    
    
    
</article>


<script type="text/javascript">
    //<![CDATA[
    function view_attendance(id, date, page) {
        var list_url = cb_url + '/attendance/dailylist/' + date + '?page=' + page;
        $('#' + id).load(list_url);
    }

    $(document).ready(function(){
        $(document).on('click', '.datepick', function() {
            view_attendance('viewattendance', $(this).attr('data-attendance-date'), '1');
            $('.date-navigation > li').removeClass('active');
            $(this).addClass('active');
        });
    });
    

    function attendance_page(date, page) {
        view_attendance('viewattendance', date, page);
        attendance_cur_page = page;
    }

    var memos = new Array();
    <?php
    if (element('default_memo', $view)) {
        foreach (element('default_memo', $view) as $key => $val) {
            ?>
            memos[<?php echo $key; ?>] = '<?php echo html_escape($val);?>';
            <?php
        }
    }
    ?>

    function change_memo() {
        var r = Math.floor(Math.random() * <?php echo count(element('default_memo', $view)); ?>);
        if ($('#att_memo').val() == memos[r]) {
            change_memo();
            return;
        }
        $('#att_memo').val(memos[r]);
    }
    $(document).on('click', '#change_memo', change_memo);

    var is_submit_attendance = false;

    $(document).on('click', '#add_attendance', function() {
        if (is_submit_attendance === true) {
            return false;
        }

        is_submit_attendance = true;

        $('#attendanceform').validate();
        if ($('#attendanceform').valid()) // check if form is valid
        {
            // do some stuff
        }
        else
        {
            is_submit_attendance = false;
            return false;
             // just show validation errors, dont post
         }

         $.ajax({
            url : cb_url + '/attendance/update',
            type : 'POST',
            cache : false,
            data : $('#attendanceform').serialize(),
            dataType : 'json',
            success : function(data) {
                is_submit_attendance = false;
                if (data.error) {
                    alert(data.error);
                    return false;
                } else if (data.success) {
                    alert(data.success);
                    view_attendance('viewattendance', '<?php echo element('date', $view); ?>', '1');
                }
            },
            error : function(data) {
                is_submit_attendance = false;
                alert('오류가 발생하였습니다.');
                return false;
            }
        });
     });

    $(document).ready(function($) {
        view_attendance('viewattendance', '<?php echo element('date', $view); ?>', '1');
    });
    $(function() {
        $('#attendanceform').validate({
            rules: {
                memo : { required:true
                    <?php if ($this->cbconfig->item('attendance_memo_length')) {?>
                        , maxlength:<?php echo $this->cbconfig->item('attendance_memo_length'); ?>
                        <?php } ?>
                    }
                }
            });
    });
    $(document).on('click', '.view_point', function() {
        $('.alert-point-policy').slideToggle();
    });
    //]]>


    $(document).ready(function(){
       var wid = $('.attendance_box').width() - 81;
       $('#att_memo').css('width' , wid);
    });
</script>
