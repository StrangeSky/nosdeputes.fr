#!/usr/bin/perl
use URI::Escape;
use HTML::Entities;
use Encode;
use utf8;

$file = shift;
$yml = shift;
$display_text = shift;

my %question;
$question{'source'} = uri_unescape($file);
$question{'source'} =~ s/^[^\/]+\///;
if ($question{'source'} =~ /questions\/base\/(\d{4})\/qSEQ\d{4}(\d{4,5})([a-z])?.html/i) {
  $question{'numero'} = uc($2.$3);
#  $question{'annee'} = $1;
  $type = $3;
}

open(FILE, $file) ;
@string = <FILE>;
$string = "@string";
close FILE;
utf8::decode($string);
$string = decode_entities($string);
$string =~ s/(<t\w+)\s*[^>]* colspan="(\d+)"[^>]*>/$1colspan$2>/g;
$string =~ s/(<[^a!][\w]*)\s*[^>]*>/$1>/g;
$string =~ s/<a[^>]*href=["']([^"']+)["'][^>]*>/<a href='$1'>/g;
$string =~ s/colspan(\d+)/ colspan='$1'/g;
$string =~ s/<\/?(div|center)>//g;
$string =~ s/\r//g;
$string =~ s/ +/ /g;
$string =~ s/\n\s+/\n/g;
$string =~ s/\s+\n/\n/g;
$string =~ s/^(.*\n)*<!-- START : primary -->//;
$string =~ s/\n<!-- END : primary -->(\n.*)*$//;
$string =~ s/<![^>]*>//g;
$string =~ s/\n([^<])/ $1/g;
$string =~ s/<br>/<br\/>/g;
$string =~ s/\n<\//<\//g;
$string =~ s/> +/>/g;
$string =~ s/(>)(<[phult1-9]+)/$1\n$2/g;
$string =~ s/\n+/\n/;
$string =~ s/(<t[dh]>)\n?<p>/$1/g;
$string =~ s/<\/p>\n?(<\/t[dh]>)/$1/g;
$string =~ s/(<t[dh]>[^<]+)(<[brp\/]+>\n?)+/$1 /g;
$string =~ s/(<[brp\/]+>\n?)+([^<]+<\/t[dh]>)/ $2/g;
$string =~ s/(<\/t(able|r|d|h)>)\n?/$1\n/g;
$string =~ s/\s+,/,/;

if ($display_text) {
  utf8::encode($string);
  print $string;
  exit;
}

$read_txt = 0;
foreach $line (split /\n/, $string) {
  if ($line =~ /<h1>(.*)<\/h1>/) {
    $question{'titre'} = $1;
  } elsif ($line =~ /<h2>(\d+).*législature<\/h2>/) {
    $question{'legislature'} = $1;
  } elsif ($line =~ /<h2>(Question.*)\s+n°\s*(\d+[a-z]?)\s/i) {
    $question{'type'} = $1;
    $num = uc($2);
    if ($num != $question{'numero'}) {
      print "Erreur numero question : ".$num." dans texte mais ".$question{'numero'}." dans url ".$question{'source'};
    }
  } elsif ($line =~ /<b>(M[\.mle]+) (.*)\s*<\/b>\s*\((.* - .*)\)\s*<\/h2>$/) {
    $nom_auteur = $2;
    $details = $3;
    $sexe = $1;
    if ($sexe =~ /[mle]/) {
      $sexe = "F";
    } else {
      $sexe = "H";
    }
    $question{'auteur'} = "$nom_auteur - $sexe - $details";
  } elsif ($line =~ /<h3>.*publi.*JO.*\s(\d+)\/(\d+)\/(\d{4})([ \-]+page\s+(\d+))?\s*<\/h3>/) {
    if (! $question{'date_publi'} ) {
      $question{'date_publi'} = sprintf("%04d-%02d-%02d",$3,$2,$1);
      $question{'page_question'} = $5;
      $read_txt++;
    } else {
      $question{'date_reponse'} = sprintf("%04d-%02d-%02d",$3,$2,$1);
      $question{'page_reponse'} = $5;
      if (! $question{'page_question'} && $question{'date_publi'} == $question{'date_reponse'}) {
        $question{'page_question'} = $question{'page_reponse'};
      }
    }
  } elsif ($line =~ /<i>Erratum\s*:\s*JO.*\s(\d+)\/(\d+)\/(\d{4})([ \-,]+p(age|.)\s?(\d+))?</) {
    if ($read_txt < 2) {
      $question{'date_publi'} = sprintf("%04d-%02d-%02d",$3,$2,$1);
      $question{'page_question'} = $6;
    } else {
      $question{'date_reponse'} = sprintf("%04d-%02d-%02d",$3,$2,$1);
      $question{'page_reponse'} = $6;
    }
  } elsif ($line =~ /<h3>Concerne le thème\s*:\s*(.*)<\/h3>/) {
#    $question{'theme'} = $1;
  } elsif ($line =~ /<[^>]+>[^<\.]*(En attente de r|R)éponse du (.*)<\/[^>]+>/) {
    $question{'ministere'} = $2;
    $question{'ministere'} =~ s/\s*En attente.*$//;
    $read_txt++;
  } elsif (!$question{'ministere'} && $line =~ /<p>Transmise (au|à)( [mM]([\.mle]+|onsieur|adame) l[ea'])? ?(.*)</) {
    $question{'ministere'} = $4;
  } elsif ($line =~ /<p>Transformée\s?(en )?(QOSD)?(<a[^>]+>(\d+[A-Z]?)<\/a>)?/) {
    $transformee = $4;
  } elsif ($line =~ /<h3>Rappelle la question (n°)? ?<a[^>]+>(n°)? ?(\d+[a-z]?)[^\d]/i) {
    $rappel_question = $3;
  } elsif ($read_txt && ($line =~ />(\S\s*)+</ || $line =~ /<\/?t(able|d|r|h)/ || $line !~ /^</)) {
    if ($line =~ /^([^<].*)$/) {
      $line = "<p>".$line."</p>";
    }
    if ($line =~/<p>La question (a été retirée( pour cause de )?|est )(.*)?</) {
      $question{'motif_retrait'} = $3;
      if (! $question{'motif_retrait'}) {
        $question{'motif_retrait'} = "retrait";
      }
      $read_txt++;
      next;
    }
    $texte = $line;
    $texte =~ s/^<p>[^M\w\.\)]+\s*((<[pbrr\/]+>)+)?([<M])/$3/;
    $texte =~ s/^([^<])/<p>$1/;
    if ($texte =~ /<t[dh]/) {
      $texte =~ s/<br\/?>/ /g;
    }
    $texte =~ s/<[pbr\/]+>Voir la vidéo<[pbr\/]+>/<br\/>/g;
    $texte =~ s/(\s*<br\/?>)+/<\/p><p>/g;
    $texte =~ s/"([^"]*)"/« $1 »/g;
    $texte =~ s/^(<\/p>)+//g;
    $texte =~ s/(<p>)+$//g;
    $texte =~ s/^<a/<p><a/;
    $texte =~ s/(<\/table>)<\/p>/$1/;
    $texte =~ s/<p>(<table>)/$1/;
    if ($read_txt == 1) {
      $question{'question'} .= $texte;
    } else {
      $question{'reponse'} .= $texte;
    }
  }
}


sub clean_texte {
  $txt = shift;
  $txt =~ s/(<\/?p><\/?p>)+<p>/<p>/g;
  $txt =~ s/<\/p>(<\/?p><\/?p>)+/<\/p>/g;
  $txt =~ s/^<p>\s*(<\/?p>\s*)+/<p>/;
  $txt =~ s/(\s*<\/?p>)+\s*<\/p>$/<\/p>/;
  return $txt;
}
$question{'question'} = clean_texte($question{'question'});
$question{'reponse'} = clean_texte($question{'reponse'});


if (! $question{ministere} ) {
  $question{ministere} = $question{'question'};
  $question{ministere} =~ s/^<p>La parole([^<]+)<\/p><p>M([\.mle]+|onsieur|adame)\s+$nom_auteur\.?[\s,]((\S+\s+)+)?[mM]a question[\s,](\S+\s+)+[mM]([\.mle]+|onsieur|adame)\sl[ea']\s?([^<\.]+)[\.<].*$/$7/;
  $question{ministere} =~ s/^<p>([^<\.]+,)?M([\.mle]+|onsieur|adame)\s+$nom_auteur\s+(\S+\s+)+[mM]([\.mle]+|onsieur|adame)\s+l[ea']\s?([^<\.]+)[\.<].*$/$5/;
  $question{ministere} =~ s/^<p>.*[mM]([\.mle]+|onsieur|adame)\s+l[ea']\s?((premier\s+)?(ministre|secrétaire|haut[\s\-]commissaire)[^<\.]+)[\.<].*$/$2/i;
  $question{ministere} =~ s/[,\s](sur|les\stermes|des\sconditions|au\s(sujet|regard)|à\spropos|de\sbien\svouloir|quant|de\s(lui|sa)|si|s'il|concernant|qu[ilunesaxà']+|d(e\s|')(s'engag|precis|expos|accord)er|sa\squestion|dans|tou(s|te)|le\scas|vise\s(à|au)|une?|suite\s(à|au)|la\ssituation|entend|l'amendement)[\s,].*($sujet)?.*$//i;
}

$question{ministere} =~ s/^<p>//;
$question{ministere} =~ s/^(ministre\sd'[ÉEé]tat,\s)?(garde\sdes\ssceaux,\s)?ministre/Ministère/ig;
$question{ministere} =~ s/\s*,\s*porte[\s\-]parole\sdu\sgouvernement//ig;
$question{ministere} =~ s/^secrétaire/Secrétariat/ig;
$question{ministere} = ucfirst(lc($question{ministere}));
$question{ministere} =~ s/haut[\-\s]commissa(ire|riat)/Haut Commissariat/ig;
$question{ministere} =~ s/\s*,\s*$//g;
$question{ministere} =~ s/(technologies\svertes\set\sdes\snégo)[ct](iations)(\ssur\sle\sclimat)?$/$1c$2 sur le climat/i;
$question{ministere} =~ s/education/éducation/ig;
$question{ministere} =~ s/[Ée]tat/état/ig;


$leg = $question{legislature};
if ($rappel_question) {
  $question{'question'} =~ s/(question n°\s*)($rappel_question)/<a href='\/question\/QE\/$leg\/$2'>$1$2<\/a>/;
}
if ($transformee) {
  $question{'question'} = "<p><em>Cette question a été transformée en <a href='/question/QE/$leg/$transformee'>question N°&nbsp;$transformee</a>.</em></p>".$question{'question'};
}

if ($question{'question'} =~ /^<p>[a-zéèêîôà]/) {
  $intro = "ZZZM";
  if ($question{'auteur'} =~ / - F - /) {
    $intro .= "me";
  } else {
    $intro .= ".";
  }
  $intro .= " $nom_auteur interroge le $question{ministere} ";
  if (! $question{'question'} =~ /^<p>sur/) {
    $intro .= "sur l";
    if ($question{'question'} =~ /^<p>[aeiouyhéèêîôà]/) {
      $intro .= "'";
    } else {
      $intro .= "e ";
    }
    if ($question{'question'} =~ /^<p>[^<]+s<\//) {
      $intro .= "s";
    }
  }
  $question{'question'} =~ s/^<p>/<p>$intro/;
}

foreach $k (keys %question) {
  utf8::encode($question{$k});
}

if ($yml) {
  foreach $k (keys %question) {
    print "  ".lc($k).": ".$question{$k}."\n";
  }
  exit;
}

print '{"source": "'.$question{'source'}.'", "legislature": "'.$question{'legislature'}.'", "type": "'.$question{'type'}.'", "numero": "'.$question{'numero'}.'", "date_question": "'.$question{'date_publi'}.'", "date_reponse": "'.$question{'date_reponse'}.'", "page_question": "'.$question{'page_question'}.'", "page_reponse": "'.$question{'page_reponse'}.'", "ministere": "'.$question{'ministere'}.'", "titre": "'.$question{'titre'}.'", "question": "'. $question{'question'}.'", "reponse": "'.$question{'reponse'}.'", "motif_retrait": "'.$question{'motif_retrait'}.'", "auteur": "'.$question{'auteur'}.'" } '."\n";

