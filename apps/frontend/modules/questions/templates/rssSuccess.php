<?php

$feed->setTitle("Les dernières questions écrites portant sur \"".$mots."\"");
$feed->setLink('http://'.$_SERVER['HTTP_HOST'].url_for('@search_questions_ecrites_mots?search='.$mots));
$i = 0;

$query->limit(10);
foreach($query->execute() as $q)
{
  $item = new sfFeedItem();
  $item->setTitle($q->getTitre());
  $item->setLink('http://'.$_SERVER['HTTP_HOST'].url_for($q->getLink()));
  $item->setAuthorName($q->Parlementaire->nom);
  $item->setPubdate(strtotime($q->date));
  $item->setUniqueId(get_class($q).$q->id);
  $item->setDescription($q);
  $feed->addItem($item);
}
decorate_with(false);
echo $feed->asXml();
