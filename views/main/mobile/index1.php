<style>
    .store_li ul{
        padding:0 3%;
    }

    .store_li li{
        width: 32%;
        margin-right: 2%;
        float: left;
    }

    .store_li li:nth-child(3n){
        margin-right: 0;
    }
</style>

<article class="wrap01">
    <!--<section class="loing_join">
        <ul class="small_font">
            <?php
            if($this->member->is_member()){
                echo '<li onClick=\'location.href="'.site_url('mypage').'";\'  title="마이페이지">
                <figure><img src="'.base_url('assets/images/spoon_'.$this->member->item('mem_level').'.png').'" alt="spoon"><figcaption>'.$this->member->item('mem_nickname').
                '</figcaption></figure></li>';
                echo '<li>|</li>';
                echo '<li>포인트 '.number_format($this->member->item('mem_point')).'P</li>';
                echo '<li>|</li>';
                 echo '<li onClick=\'location.href="'.site_url('mypage').'";\'>회원정보</li>';
                 echo '<li>|</li>';
                 echo '<li onClick=\'location.href="'.site_url('login/logout?url=' . urlencode(current_full_url())).'";\'  title="로그아웃">로그아웃</li>';
            } else {


                echo '<li style="width:49%; text-align:right; padding-right:3%;" onClick=\'location.href="'.site_url('login?url=' . urlencode(current_full_url())).'";\'  title="로그인" style="text-align:right;">로 그 인</li>';
                echo '<li>|</li>';
                echo '<li style="width:49%; text-align:left; padding-left:3%;" onClick=\'location.href="'.site_url('login?url=' . urlencode(current_full_url())).'";\'  title="회원가입" style="text-align:left;"">회 원 가 입</li>';
            }
            ?>
        </ul>
    </section>-->

    <section class='main_title store_li'>
        <h2>스 토 어</h2>
        <span>적립된 포인트로 다양한 혜택을 누려보세요.</span>
        <ul>
            <li>aaaa</li>
            <li>aaaa</li>
            <li>aaaa</li>
            <li>aaaa</li>
            <li>aaaa</li>
            <li>aaaa</li>
            <li>aaaa</li>
            <li>aaaa</li>
            <li>aaaa</li>
            <li>aaaa</li>
        </ul>
    </section>


</article>
