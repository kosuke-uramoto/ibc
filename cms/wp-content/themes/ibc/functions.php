<?php
if (!defined('ABSPATH')) { exit; }
function ibc_courses() { return ['hotel'=>'ホテル・ブライダル','railway'=>'鉄道','international'=>'国際ビジネス','airline'=>'エアライン','it'=>'ITビジネス']; }
add_action('after_setup_theme', function () { add_theme_support('title-tag'); add_theme_support('post-thumbnails'); });
add_action('init', function () {
 register_post_type('ibc_oc', ['labels'=>['name'=>'オープンキャンパス','singular_name'=>'開催回','add_new_item'=>'開催回を追加','edit_item'=>'開催回を編集','featured_image'=>'イメージ画像（任意）','set_featured_image'=>'イメージ画像を設定','remove_featured_image'=>'イメージ画像を削除','use_featured_image'=>'この画像を使用'], 'public'=>true,'show_in_rest'=>true,'menu_icon'=>'dashicons-calendar-alt','supports'=>['title','editor','thumbnail'],'rewrite'=>['slug'=>'oc'],'has_archive'=>true]);
});
add_action('add_meta_boxes', function () {
 add_meta_box('ibc_oc_fields','開催情報・学科別体験','ibc_oc_fields','ibc_oc','normal','high');
 add_meta_box('ibc_related','関連オープンキャンパス','ibc_related_fields','post','side');
});
function ibc_oc_fields($post) {
 wp_nonce_field('ibc_save','ibc_nonce');
 echo '<p>デモ用の入力画面です。午前・午後はそれぞれ別の開催回として登録します。空欄の学科は掲載されません。</p><p>画像を載せる場合は、編集画面の「イメージ画像（任意）」からアップロードまたはメディアライブラリの画像を選び、投稿を更新してください。未設定なら画像なしで表示します。</p>';
 $fields=['start'=>['開催開始日時（日本時間）','datetime-local'],'end'=>['開催終了日時（日本時間）','datetime-local'],'session'=>['午前／午後など','text'],'audience'=>['対象者','text'],'venue'=>['集合場所','text']];
 foreach($fields as $key=>$info) { echo '<p><label for="ibc_'.$key.'"><strong>'.esc_html($info[0]).'</strong></label><br><input style="width:100%;max-width:540px" id="ibc_'.$key.'" name="ibc['.$key.']" type="'.$info[1].'" value="'.esc_attr(get_post_meta($post->ID,'_ibc_'.$key,true)).'"></p>'; }
 echo '<p><label>開催状況 <select name="ibc[state]">';
 foreach(['open'=>'開催予定','closed'=>'受付終了','cancelled'=>'中止'] as $value=>$label) { echo '<option value="'.$value.'" '.selected(get_post_meta($post->ID,'_ibc_state',true),$value,false).'>'.$label.'</option>'; } echo '</select></label></p>';
 foreach(ibc_courses() as $key=>$label) { echo '<p><label for="ibc_'.$key.'"><strong>'.esc_html($label).'：体験内容</strong></label><br><textarea style="width:100%" rows="2" id="ibc_'.$key.'" name="ibc['.$key.']">'.esc_textarea(get_post_meta($post->ID,'_ibc_'.$key,true)).'</textarea></p>'; }
 echo '<p>フォーム連携は保留です。申込ボタンは設けず、詳細への案内を表示します。</p>';
}
function ibc_related_fields($post) {
 wp_nonce_field('ibc_related_save','ibc_related_nonce');
 echo '<select name="ibc_related" style="width:100%"><option value="0">関連なし</option>';
 foreach(get_posts(['post_type'=>'ibc_oc','posts_per_page'=>-1,'post_status'=>'publish']) as $oc) { echo '<option value="'.esc_attr($oc->ID).'" '.selected(get_post_meta($post->ID,'_ibc_related',true),$oc->ID,false).'>'.esc_html($oc->post_title).'</option>'; } echo '</select><p>選んだOCの最新情報を記事末尾に表示します。</p>';
}
function ibc_datetime($value) {
 $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', (string)$value,new DateTimeZone('Asia/Tokyo'));
 return $date && $date->format('Y-m-d\TH:i')===$value ? $date : false;
}
add_action('save_post',function($id) {
 if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || wp_is_post_revision($id) || !current_user_can('edit_post',$id)) return;
 if (get_post_type($id)==='post' && isset($_POST['ibc_related_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ibc_related_nonce'])),'ibc_related_save')) {
  $related=absint($_POST['ibc_related']??0); update_post_meta($id,'_ibc_related',get_post_type($related)==='ibc_oc'?$related:0);
 }
 if(get_post_type($id)!=='ibc_oc' || !isset($_POST['ibc_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ibc_nonce'])),'ibc_save')) return;
 $data=isset($_POST['ibc']) && is_array($_POST['ibc']) ? wp_unslash($_POST['ibc']) : [];
 foreach(array_merge(['start','end','session','audience','venue','state'],array_keys(ibc_courses())) as $key) {
  $value=isset($data[$key]) && is_string($data[$key]) ? sanitize_textarea_field($data[$key]) : '';
  if(in_array($key,['start','end'],true) && !ibc_datetime($value)) $value='';
  if($key==='state' && !in_array($value,['open','closed','cancelled'],true)) $value='open';
  update_post_meta($id,'_ibc_'.$key,$value);
 }
});
add_action('admin_notices',function(){
 if(!isset($_GET['post']) || get_post_type(absint($_GET['post']))!=='ibc_oc') return;
 $id=absint($_GET['post']); $a=ibc_datetime(get_post_meta($id,'_ibc_start',true)); $b=ibc_datetime(get_post_meta($id,'_ibc_end',true));
 if(!$a || !$b || $b<=$a) echo '<div class="notice notice-warning"><p>開催開始・終了日時を設定してください。終了は開始より後にします。不正な日時の開催回は一覧に掲載されません。</p></div>';
});
function ibc_event($id) {
 if(get_post_type($id)!=='ibc_oc' || get_post_status($id)!=='publish') return null;
 $start=ibc_datetime(get_post_meta($id,'_ibc_start',true)); $end=ibc_datetime(get_post_meta($id,'_ibc_end',true));
 if(!$start || !$end || $end<=$start) return null;
 $programs=[]; foreach(ibc_courses() as $key=>$label) { $text=get_post_meta($id,'_ibc_'.$key,true); if($text!=='') $programs[]=['key'=>$key,'label'=>$label,'text'=>$text]; }
 $state=get_post_meta($id,'_ibc_state',true)?:'open'; if($state!=='cancelled' && $end->getTimestamp()<=time()) $state='ended';
 $image=null; $image_id=get_post_thumbnail_id($id);
 if($image_id && wp_attachment_is_image($image_id)) {
  $source=wp_get_attachment_image_src($image_id,'large');
  if($source) $image=['id'=>$image_id,'url'=>$source[0],'width'=>$source[1],'height'=>$source[2],'alt'=>(string)get_post_meta($image_id,'_wp_attachment_image_alt',true)];
 }
 return ['image'=>$image,'id'=>$id,'title'=>get_the_title($id),'url'=>get_permalink($id),'start'=>$start->format(DATE_ATOM),'end'=>$end->format(DATE_ATOM),'session'=>get_post_meta($id,'_ibc_session',true),'audience'=>get_post_meta($id,'_ibc_audience',true),'venue'=>get_post_meta($id,'_ibc_venue',true),'state'=>$state,'programs'=>$programs];
}
add_action('rest_api_init',function(){ register_rest_route('ibc/v1','/feed',['methods'=>'GET','permission_callback'=>'__return_true','callback'=>function(){
 $events=[]; foreach(get_posts(['post_type'=>'ibc_oc','post_status'=>'publish','posts_per_page'=>-1,'meta_key'=>'_ibc_start','orderby'=>'meta_value','order'=>'ASC']) as $p) { $event=ibc_event($p->ID); if($event && $event['state']!=='ended' && strtotime($event['end'])>time()) $events[]=$event; }
 $news=[]; foreach(get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>6]) as $p) $news[]=['title'=>get_the_title($p),'date'=>get_the_date('Y.m.d',$p),'url'=>get_permalink($p)];
 $response=new WP_REST_Response(['events'=>$events,'news'=>$news]); $response->header('Cache-Control','no-store, max-age=0'); return $response;
 }]); });
function ibc_event_html($event) {
 if(!$event) return;
 $labels=['open'=>'開催予定','closed'=>'受付終了','cancelled'=>'中止','ended'=>'開催終了'];
 echo '<section class="event">';
 if(!empty($event['image']['id'])) echo wp_get_attachment_image($event['image']['id'],'large',false,['class'=>'oc-image','loading'=>'lazy','decoding'=>'async']);
 echo '<p class="eyebrow">OPEN CAMPUS · '.esc_html($labels[$event['state']]).'</p><h2>'.esc_html($event['title']).'</h2><p class="date">'.esc_html(wp_date('Y年n月j日 H:i',strtotime($event['start']),new DateTimeZone('Asia/Tokyo'))).'–'.esc_html(wp_date('H:i',strtotime($event['end']),new DateTimeZone('Asia/Tokyo'))).'</p><p>'.esc_html($event['session'].' ／ '.$event['audience']).'</p><p>'.esc_html($event['venue']).'</p><dl>';
 foreach($event['programs'] as $p) echo '<dt>'.esc_html($p['label']).'</dt><dd>'.nl2br(esc_html($p['text'])).'</dd>';
 echo '</dl></section>';
}
add_action('admin_menu',function(){ add_theme_page('モックアップの準備','モックアップの準備','manage_options','ibc-demo','ibc_demo_page'); });
function ibc_demo_page(){
 if(!current_user_can('manage_options')) return;
 if(isset($_POST['ibc_demo_create']) && check_admin_referer('ibc_demo')) {
  if(!get_option('ibc_demo_seeded')) {
   $day=new DateTimeImmutable('next saturday',new DateTimeZone('Asia/Tokyo')); $ids=[];
   foreach([['10:00','11:00','午前','学校・入試説明会'],['13:00','16:00','午後','学科の仕事を体験するオープンキャンパス']] as $slot) {
    $id=wp_insert_post(['post_type'=>'ibc_oc','post_status'=>'publish','post_title'=>'【デモ】'.$slot[3],'post_content'=>'モックアップ用の仮情報です。実際の開催案内ではありません。'],true);
    if(is_wp_error($id)) { echo '<div class="notice notice-error"><p>データを追加できませんでした。</p></div>'; return; }
    $ids[]=$id;
    $meta=['start'=>$day->format('Y-m-d').'T'.$slot[0],'end'=>$day->format('Y-m-d').'T'.$slot[1],'session'=>$slot[2],'audience'=>'全学年','venue'=>'IBC校内（仮）','state'=>'open','hotel'=>'ソースアート体験','railway'=>'内定者記者会見','international'=>'英語でコミュニケーション体験','airline'=>'空港のお仕事体験','it'=>'プログラミング体験'];
    if($slot[2]==='午前') foreach(array_keys(ibc_courses()) as $key) $meta[$key]='学校・入試説明会';
    foreach($meta as $key=>$value) update_post_meta($id,'_ibc_'.$key,$value);
   }
   $news=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'【デモ】次回オープンキャンパスのご案内','post_content'=>'各学科の体験内容をご紹介します。記事下部の開催情報は、OCの登録内容に連動します。']);
   if($news) update_post_meta($news,'_ibc_related',$ids[1]);
   update_option('ibc_demo_seeded',true);
  }
  echo '<div class="notice notice-success"><p>デモデータを準備しました。左メニューのオープンキャンパスから編集できます。</p></div>';
 }
 echo '<div class="wrap"><h1>IBC モックアップの準備</h1><p>次の土曜日の午前・午後のOCと、関連ニュースを公開状態で追加します。内容には「デモ」と明記されます。一度だけ実行できます。</p><form method="post">'; wp_nonce_field('ibc_demo'); submit_button('デモデータを追加','primary','ibc_demo_create'); echo '</form><p>追加後は静的ページを再読み込みすると更新内容が表示されます。</p></div>';
}
