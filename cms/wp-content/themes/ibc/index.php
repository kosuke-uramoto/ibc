<?php if (!defined('ABSPATH')) exit; ?><!doctype html>
<html lang="ja"><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/ibc.css"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?><div class="demo">IBC リニューアル検討用モックアップ・掲載内容は仮情報です</div><header><a class="brand" href="/">IBC <span>西鉄国際ビジネスカレッジ</span></a><nav><a href="/oc/">Open Campus</a><a href="/news/">お知らせ</a></nav></header><main>
<?php if(have_posts()): while(have_posts()): the_post(); ?>
<article class="article"><p class="eyebrow"><?php echo get_post_type()==='ibc_oc'?'OPEN CAMPUS':'NEWS'; ?></p><h1><?php if(!is_singular()) echo '<a href="'.esc_url(get_permalink()).'">'; the_title(); if(!is_singular()) echo '</a>'; ?></h1>
<?php if(is_singular()): ?><div class="body-copy"><?php the_content(); ?></div><?php
if(get_post_type()==='ibc_oc') { $event=ibc_event(get_the_ID()); if($event) ibc_event_html($event); else echo '<p>開催情報は準備中です。</p>'; }
else { $event=ibc_event(absint(get_post_meta(get_the_ID(),'_ibc_related',true))); if($event) { ibc_event_html($event); echo '<p><a class="button" href="'.esc_url($event['url']).'">OC詳細を見る →</a></p>'; } }
else: the_excerpt(); endif; ?></article>
<?php endwhile; the_posts_pagination(); else: ?><h1>掲載情報は準備中です</h1><?php endif; ?>
<p><a href="/">← トップへ戻る</a></p></main><footer>IBC · Open Campus Preview</footer><?php wp_footer(); ?></body></html>
