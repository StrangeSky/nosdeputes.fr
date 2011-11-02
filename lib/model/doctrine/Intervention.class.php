<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class Intervention extends BaseIntervention
{

  private static $seances = null;
  private static $personnalites = null;

  public function getLink() {
    sfProjectConfiguration::getActive()->loadHelpers(array('Url'));
    return url_for('@interventions_seance?seance='.$this->getSeance()->id).'#inter_'.$this->getMd5();
  }

  public function getPersonne() {
    return $this->getNomAndFonction();
  }

  public function getFullDate() {
    $datetime = strtotime($this->date);
    $moment = $this->Seance->moment;
    $heuretime = "10:00";
    if (preg_match('/\d:\d/', $moment))
      $heuretime = $moment;
    else if (preg_match('/^(\d)/', $moment, $match))
      $heuretime = sprintf('%02d', 10+4*($match[1]-1)).':00';
    $datetime += strtotime($heuretime) - strtotime('now');
    $timestamp = $this->timestamp;
    $len = strlen($timestamp);
    if ($len > 6)
      $timestamp = substr($timestamp, $len-6, 6) + 0;
    $datetime += $timestamp;
    return date('Y-m-d H:i:s', $datetime);
  }

  public function __toString() {
    if (strlen($this->intervention) > 1024)
      return substr($this->intervention, 0, 512).'...';
    return $this->intervention;
  }

  public function getTitre() {
    if ($this->type === 'question')
      $titre = 'Question orale du ';
    else {
      $titre = 'Intervention';
      if ($this->type === 'commission')
        $titre .= ' en commission';
      else
        $titre .= ' en hémicycle';
      $titre .= ' le ';
    }
    $titre .= myTools::displayShortDate($this->date);
    if ($this->type === 'question')      
      $titre .= ' : '.ucfirst($this->Section->getTitre());
    else if ($this->type === 'loi')
      $titre .= ' : '.ucfirst($this->Section->Section->getTitreComplet());
    return $titre;
  }

  public function setSeance($type, $date, $heure, $session, $commissiontxt = null) {
    $this->setType($type);
    if (is_array(self::$seances)) {
      if (isset(self::$seances[$type.$date.$heure.$session.$commissiontxt])) {
	return $this->_set('seance_id', self::$seances[$type.$date.$heure.$session.$commissiontxt]);
      }else {
	self::$seances = array();
      }
    }else{
      self::$seances = array();
    }
    if ($type == 'commission') {
      $commission = Doctrine::getTable('Organisme')->findOneByNomOrCreateIt($commissiontxt, 'parlementaire');
      $seance = $commission->getSeanceByDateAndMomentOrCreateIt($date, $heure, $session);
    } else{
      $seance = Doctrine::getTable('Seance')->findOneOrCreateIt('hemicycle', $date, $heure, $session);
    }
    $id = $this->_set('seance_id', $seance->id);
    self::$seances[$type.$date.$heure.$session.$commissiontxt] = $seance->id;
    return $id;
  }
  public function setPersonnaliteByNom($nom, $fonction = null) 
  {
    $nom = html_entity_decode($nom, ENT_COMPAT, 'UTF-8');
    $fonction = html_entity_decode($fonction, ENT_COMPAT, 'UTF-8');
    $this->setFonction($fonction);
    if (is_array(self::$personnalites)) {
      if (isset(self::$personnalites[$nom.$fonction])) {
	if (isset(self::$personnalites[$nom.$fonction]['personnalite'])) {
	  return $this->setPersonnalite(self::$personnalites[$nom.$fonction]['personnalite']);
	}else{
	  return $this->setParlementaire(self::$personnalites[$nom.$fonction]['parlementaire']);
	}
      }
    }else{
      self::$personnalites = array();
    }
    if (!preg_match('/ministre|secr[^t]+taire [^t]+tat|commissaire|garde des sceaux/i', $fonction)) { 
      $personne = Doctrine::getTable('Parlementaire')->findOneByNom($nom);
      if (!$personne && ($this->type != "commission" || $fonction == null || preg_match('/(sénateur|sénatrice|rapporteur|présidente?$|présidente? de la commission)/i', $fonction))) {
	$personne = Doctrine::getTable('Parlementaire')->similarTo($nom);
      }
      if ($personne) {
	self::$personnalites[$nom.$fonction] = array('parlementaire' => $personne);
	return $this->setParlementaire($personne);
      }
    }
    $personne = Doctrine::getTable('Personnalite')->findOneByNom($nom);
    if (!$personne) {
      $personne = new Personnalite();
      $personne->setNom($nom);
      $personne->save();
    }
    if ($personne) {
      self::$personnalites[$nom.$fonction] = array('personnalite' => $personne);
      return $this->setPersonnalite($personne);
    }
  }
  public function setParlementaire($parlementaire, $from_db = null) {
    if (isset($parlementaire->id)) {
      $this->_set('parlementaire_id', $parlementaire->id);
      $this->_set('personnalite_id', null);
      if (!$from_db)
        $this->getSeance()->addPresence($parlementaire, 'intervention', $this->source);
    }
  }
  public function setPersonnalite($personne) {
    if (isset($personne->id)) {
      $this->_set('parlementaire_id', null);
      $this->_set('personnalite_id', $personne->id);
    }
  }

  public function hasIntervenant() {
    if ($this->parlementaire_id) {
      return true;
    }
    if ($this->personnalite_id) {
      return true;
    }
    return false;
  }

  public function getIntervenant() {
    $perso = $this->Parlementaire;
    if (!$perso->id)
      $perso = $this->Personnalite;
    return $perso;
  }

  public function getNomAndFonction() {
    $res = null;
    if ($this->hasIntervenant()) {
      $res = $this->getIntervenant()->getNom();
      if ($this->getFonction())
	$res .= ', '.$this->getFonction();
    }
    return $res;
  }

  private static function prepareLois($tlois) {
    $tlois = preg_replace('/[^,\d\-]+/', '', $tlois);
    $tlois = preg_replace('/\s+,/', ',', $tlois);
    $tlois = preg_replace('/,\s+/', ',', $tlois);
    return explode(',', $tlois); 
  }

  public function updateTagLois($strlois) {
    $lois = self::prepareLois($strlois);
    $this->addTagLois($lois);
    $this->tagSectionLois($lois);
  }

  public function addTagLois($lois) {
    $loisstring = "";
    foreach($lois as $loi) if ($loi) {
      $tag = 'loi:numero='.$loi;
      $this->addTag($tag);
      if ($loisstring == "") $loisstring = "t.numero = $loi";
      else $loisstring .= " OR t.numero = $loi";
    }
    return $loisstring;
  }

  public function tagSectionLois($lois) {
     if ($this->Section && $this->section_id != 1) {
        $titre = $this->Section->Section->getTitre();
        if (!(preg_match('/(conf.*rence des pr.*sidents|^(d.*p.*t|transmission) d(e |.une? |.)(documents?|rapport|proposition|avis|projet)|cloture|ouverture|question|ordre du jour|calendrier|élection.*nouveau|démission|reprise|examen simplifié|cessation.*mandat|proclamation|souhaits|application de l.article|renvoi pour avis|nomination (de|d.une?) (membre|rapporteur)s?|rappel au règlement|^communication|^candidature|examen.*pétition)/i', $titre))) {
          foreach($lois as $loi) {
            $tag = 'loi:numero='.$loi;
            $this->Section->addTag($tag);
            if ($this->Section->section_id && $this->Section->Section->id && $this->Section->section_id != $this->section_id)
              $this->Section->Section->addTag($tag);
          }
        }
      }
  }

  public function setContexte($contexte, $date = null, $timestamp = null, $tlois = null, $debug = 0) {
    $lois = self::prepareLois($tlois);
    $loisstring = $this->addTagLois($lois);
    if ($date && preg_match("/^(\d{4}-\d\d-\d\d)/", $date, $annee)) {
      if (!preg_match("/^".$annee[1]."\d\d:\d\d$/", $date))
        $date = $annee[1]."00:00";
    } else print "WARNING : Intervention $this->id has incorrect date : $date";

    if (!isset($lois[0]) || !$lois[0]) {
      $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt($contexte, $date, $timestamp));
      return $debug;
    }
    $urls = Doctrine_Query::create()
        ->select('distinct(t.id_dossier_institution)')
        ->from('Texteloi t')
        ->where('t.type = ? OR t.type = ? OR t.type = ? OR t.type = ?', array("Proposition de loi", "Proposition de résolution", "Projet de loi", "Texte de la commission"))
        ->andWhere($loisstring)
        ->fetchArray();
    $ct = count($urls);
    if ($ct == 0) $urls = Doctrine_Query::create()
        ->select('distinct(t.id_dossier_institution)')
        ->from('Texteloi t')
        ->where($loisstring)
        ->fetchArray();
    $ct = count($urls);
    if ($ct > 1) {
        $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt($contexte, $date, $timestamp));
        if ($debug) {
          print "WARNING : Intervention $this->id has tags lois corresponding to multiple id_dossier_institutions : ";
          foreach ($urls as $url)
            print $url['distinct']." ; ";
          print " => Saving to section $this->Section->id\n";
          $debug = 0;
        }
        return $debug;
    }
    if ($ct == 0) $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt($contexte, $date, $timestamp));
    else if ($ct == 1) {
        $section1 = Doctrine::getTable('Section')->findOneByContexte($contexte);
        $section2 = Doctrine::getTable('Section')->findOneByIdDossierInstitution($urls[0]['distinct']);
        if ($section2) {
          if (!$section1) 
            $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt(str_replace(trim(preg_replace('/^([^>]+)(>.*)?$/', '\\1', $contexte)), $section2->titre, $contexte), $date, $timestamp));
          else if ($section1->section_id == $section2->id)
            $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt($section1->titre_complet, $date, $timestamp));
          else {
            $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt($contexte, $date, $timestamp));
            if ($debug) {
              print "WARNING : Intervention $this->id has tags lois corresponding to another section $section2->id";
              print " => Saving to section ".$this->Section->id."\n";
              $debug = 0;
            }
            return $debug;
          }
        }
        else {
          $section1 = Doctrine::getTable('Section')->findOneByContexteOrCreateIt($contexte, $date, $timestamp);
          $this->setSection($section1);
          $section1->setIdDossierInstitution($urls[0]['distinct']);
          $section1->save();
       }
    }
    $this->tagSectionLois($lois);
    return $debug;
  }

  public function setAmendements($tamendements) {
    $tamendements = preg_replace('/[^,\d]+/', '', $tamendements);
    $amends = preg_split('/\s*,\s*/', $tamendements);
    foreach($amends as $amend) {
      $tag = 'loi:amendement='.$amend;
      $this->addTag($tag);
    }
  }
  
  public function setIntervention($s) {
    $s = str_replace(html_entity_decode('&nbsp;', ENT_COMPAT, "UTF-8"), ' ', $s);
    $this->_set('nb_mots', str_word_count($s));
    return $this->_set('intervention', $s);
  }

  public function getIntervention($args = array()) {
    $intertot = $this->_get('intervention');
    if ($this->type != 'question' && isset($args['linkify_amendements']) && $linko = $args['linkify_amendements']) {
      $intertot = preg_replace('/\(([^\)]+)\)/', '(<i>\\1</i>)', $intertot);
      $interres = '';
      foreach (explode('</p>', $intertot) as $inter) {
       //Repère les amendements (pour les linkifier)
       if (preg_match_all('/(amendements?[,\s]+(identiques?)?[,\s]*)((n[°os\s]*|([ABICOM]+-)?\d+\s*|,\s*|à\s*|et\s*|rectifié\s*)+)/', $inter, $match)) {
	$lois = implode(',', $this->getTags(array('is_triple' => true,
						  'namespace' => 'loi',
						  'key' => 'numero',
						  'return' => 'value')));
	if ($lois) for ($i = 0 ; $i < count($match[0]) ; $i++) {
	  $match_protected = preg_replace('/(\s*)([ABICOM\-]*\d[\d\s\à]*rectifiés?|[ABICOM\-]*\d[\d\s\à]*)(,\s*|\s*et\s*)*/', '\1%\2%\3', $match[3][$i]);
	  if (preg_match_all('/\s*%([^%]+)%(,\s*|\s*et\s*)*/', $match_protected, $amends)) {
	    $replace = $match_protected;
	    foreach($amends[1] as $amend) {
	      $am = preg_replace('/à+/', '-', $amend);
	      $am = strtoupper(preg_replace('/[^\d\-ABICOM]+/', '',$am));
              if ($this->type == 'commission' && !preg_match("/COM/", $am))
                $am = "COM-".$am;
	      $link = str_replace('LLL', urlencode($lois), $linko);
	      $link = str_replace('AAA', urlencode($am), $link);
	      $replace = preg_replace('/%'.$amend.'%/', '<a name="amend_'.$am.'" href="'.$link.'">'.$amend.'</a>', $replace);
	    }
	    $inter = preg_replace('/'.$match[1][$i].$match[3][$i].'/', $match[1][$i].$replace, $inter);
	  }
	}
      }

      //Repère les documents parlementaires (pour les linkifier)
      if (preg_match_all('/(projet|proposition|annexe|rapport|avis)[^<°]+[<i>]*(n[os°\s<\/up>]+)(([\s,;\w°]{0,8}\W*\d+([\s,\d\(\)\[\]\-])?)+)/i', $inter, $matches)) {
	$match = $matches[3];
        sfProjectConfiguration::getActive()->loadHelpers(array('Url'));
	for($i = 0 ; $i < count($match) ; $i++) if (!preg_match('/ du /', $match[$i]) && !preg_match('/^\D*\d\d\d\d\D*$/', $match[$i])) {
		$match[$i] = preg_replace('/[, ]+et[, ]+/', ', ', $match[$i]);
        	$matche = explode(';', $match[$i]);
		if (count($matche) == 1 && preg_match('/\d\d\d(\d|\D+\d\d\d)/', $match[$i]))
			$matche = explode(',', $matche[0]);
		$loie = $matche;
		for ($y = 0 ; $y < count($matche) ; $y++) if (preg_match('/\d/', $matche[$y])) {
			if (preg_match('/annexe/', $matches[1][$i]) && $i)
			     $loie[$y] = $oldloi."-".preg_replace('/^\D*(\d+)\D*$/', '\\1', $matche[$y]);
			else $loie[$y] = preg_replace('/\s*(\d+)\D+(\d+)[\s\-]+(\d+)\D*/', '\\2\\3-\\1', $matche[$y]);
	  		if (!preg_match('/\-/', $loie[$y])) {
				$loie[$y] = $this->getSeance()->getSession().'-'.preg_replace('/\D/', '', $loie[$y]);
			}
			$loie[$y] = trim($loie[$y]);
			if (strlen($loie[$y]) < 10) continue;
			$matche[$y] = preg_replace('/\D/', '.', trim($matche[$y]));
          		$inter = preg_replace('/(n[os\s<\/up>°]*)?('.$matche[$y].')/', '<a href="'.url_for('@document?id='.$loie[$y]).'">\\1\\2</a>', $inter);
			$oldloi = $loie[$y];
		}
         }
      }
      $interres .= $inter;
      }
      $intertot = $interres;
    }
    return $intertot;
  }
}
