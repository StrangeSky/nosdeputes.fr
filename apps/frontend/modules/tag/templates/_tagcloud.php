<div class="internal_tag_cloud">
<?php $keys = array_keys($tags);
if (count($keys)) { foreach($keys as $tag) : ?>
<span class="tag_level_<?php echo $tags[$tag]['class']; ?>"><a href="<?php 
echo url_for($route.'tags='.$tags[$tag]['related']); ?>" title="<?php echo $tags[$tag]['count']; ?>"><?php 
$nom = preg_replace('/\s+/', '&nbsp;', $tags[$tag]['tag']);
echo $nom; ?></a> <?php
		 ?></span><?php endforeach; 
if(isset($hide) && $hide) 
  echo '<span class="hide">finfinfinfinfinfinfinfinfinfinfinfinfinfinfinf</span>';
 } else { ?>
<span><em>Désolé, aucun mot-clé pertinent trouvé</em></span>
<?php } ?></div>