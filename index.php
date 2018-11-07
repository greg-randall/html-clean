<?php

if (!isset($_POST["input"]) | $_POST["input"] == "") { //check to see if the url is set. if not set prompts for a url.
?>Clean:
        <form action="index.php" method="post">
          <textarea name="input" rows="40" cols="80"></textarea><br /><br />
            <input type="submit">
          </form><?php
  exit();
}
else {
  $input = htmlspecialchars_decode($_POST["input"]); //get the input html and decode it


  // get all html elements on a line by themselves
  //basically we add linebreaks around anything with < or >
  $clean = str_replace('"', "'", $input);
  $clean = str_replace("<", "\n<", $clean);
  $clean = str_replace(">", ">\n", $clean);
  $clean = preg_replace("/\n(<[^ai]([\w\d]+)?).+/i", "\n$1>", $clean);
  $clean = str_replace(">\n", ">", $clean);

  //now that we have html and text on seperate lines we can start

  //this first step tries to fix capitalization of all headings
  $clean = explode("\n", $clean);
  foreach($clean as $line) {
    if (strpos($line, '<h') !== false) {
      $h = substr($line, 0, 4);
      $title = substr($line, 4);
      $clean_proper[] = $h . proper_case($title);
    }
    else {
      $clean_proper[] = $line;
    }
  }

  $clean = implode("\n", $clean_proper);//combine things back to a single line
  $clean = str_replace(">", ">\n", $clean);//seperate html onto seperate lines
  $clean = strip_tags($clean, '<h1><h2><h3><h4><h5><h6><img><a><em><hr><i><li><ol><p><strong><table><td><th><tr><ul>'); //remove almost all of the html code. this is an attempt to remove everything that isn't helping format the text. it might be too agressive for your application
  $clean = preg_replace("/\n(<[^ai]([\w\d]+)?).+/i", "\n$1>", $clean); //on all tags that _arent_ links or images remove all the classes, options etc, so <em class="asdf"> goes to <em>
  $clean = preg_replace("/<a.+href='([:\w\d#\/\-\.]+)'.+/i", "<a href=\"$1\">", $clean);//try and delete everything that's not the actual link in the link
  $clean = preg_replace("/<img.+src='([\w\d_:?.\/%=\-]+)'.+/i", "<img src=\"$1\">", $clean);//try and delete everything that's not the actual image in the image tag
  $clean = str_replace("'", '"', $clean);//replace all singe quotes with double quotes
  $clean = preg_replace("/[\s\n\r]+/i", " ", $clean);//remove _all_ linebreaks double spaces etc and replace with a single space
  $clean = str_replace('> <', '><', $clean); // get rid of extra space around html elements
  $clean = preg_replace("/<([\w\d]+)><\/\\1>/i", "", $clean);//try to remove all html tags with nothing in side the tag
  $clean = str_replace('<p></p>', '', $clean);//remove empty paragraphs
  $clean = str_replace('target="_blank"','',$clean);

  //commented out. this does goofy stuff with links
  //$clean = str_replace('> ', '>', $clean);//get rid of extra spaces around html tags
  //$clean = str_replace(' <', '<', $clean);//get rid of extra spaces around html tags

  //i'm dealing with some annoying wordpress shortcode cleanup, this will remove stray shortcodes.
  $clean = str_replace("[avia", "\n[avia", $clean);
  $clean = str_replace("]", "]\n", $clean);
  $clean = explode("\n", $clean);
  foreach($clean as $line) {
    if (strpos($line, '[avia') !== false) {
      $remove_shortcodes[] = "";
    }
    else {
      $remove_shortcodes[] = $line;
    }
  }
  $clean = implode("\n", $remove_shortcodes);//combine things back to a single line
  $clean = preg_replace("/[\s\n\r]+/i", " ", $clean);//remove _all_ linebreaks double spaces etc and replace with a single space

  //we're going to pass our ugly html through dirtymarkup.
  $url = 'https://www.10bestdesign.com/dirtymarkup/api/html';
  $context = stream_context_create(array(
    'http' => array(
      'method' => 'POST',
      'header' => 'Content-type: application/x-www-form-urlencoded',
      'content' => http_build_query(array(
        'code' => $clean,
        'output' => 'fragment',
      )) ,
      'timeout' => 60
    )
  ));
  $resp = file_get_contents($url, FALSE, $context);
  $resp = json_decode($resp, true);
  $resp = str_replace('<p></p>', '', array_pop($resp));
  $resp = str_replace('&nbsp;', " ", $resp);
  echo "<pre>" . htmlspecialchars($resp);
}


// Converts to proper case, deals with some edge cases, and capitialzes many medical acronyms.
// this assumes that the title is completely uppercase or completely lowercase ie, we get no hints
function proper_case($title)
{ //https://gist.github.com/greg-randall/d00d83429e24807fb3b0a9071ece33e8

  // http://www.superheronation.com/2011/08/16/words-that-should-not-be-capitalized-in-titles/#comment-1945084

  $lowercase_words = array(
    'a ',    'aboard ',    'about ',    'above ',    'across ',    'after ',    'against ',    'along ',    'amid ',    'among ',    'an ',    'and ',    'anti ',    'around ',    'as ',    'at ',    'before ',    'behind ',    'below ',    'beneath ',    'beside ',    'besides ',    'between ',    'beyond ',    'but ',    'by ',    'concerning ',    'considering ',    'despite ',    'down ',    'during ',    'except ',    'excepting ',    'excluding ',    'following ',    'for ',    'from ',    'in ',    'inside ',    'into ',    'like ',    'minus ',    'near ',    'of ',    'off ',    'on ',    'onto ',    'opposite ',    'or ',    'outside ',    'over ',    'past ',    'per ',    'plus ',    'regarding ',    'round ',    'save ',    'since ',    'so ',    'than ',    'the ',    'through ',    'to ',    'toward ',    'towards ',    'under ',    'underneath ',    'unlike ',    'until ',    'up ',    'upon ',    'versus ',    'via ',    'with ',    'within ',    'without ',    'yet'
  );

  // https://www.medicinenet.com/common_medical_abbreviations_and_terms/article.htm

  $medical_acronyms = array(
    'UA','AMI ',    'B-ALL ',    'FSH ',    'HAPE ',    'HPS ',    'IBS ',    'IDDM ',    'MDS ',    'NBCCS ',    'SIDS ',    'TSH ',    'ACL ',    'AFR ',    'ADHD ',    'ADD ',    'ADR ',    'AIDS ',    'AKA ',    'ANED ',    'ADH ',    'ARDS ',    'ARF ',    'ASCVD ',    'BKA ',    'BMP ',    'BPD ',    'BSO ',    'CABG ',    'CBC ',    'CDE ',    'CPAP ',    'COPD ',    'CVA ',    'DCIS ',    'DDX ',    'DJD ',    'DNC ',    'DNR ',    'DOE ',    'DTR ',    'DVT ',    'ETOH ',    'GOMER ',    'HRT ',    'HTN ',    'IBD ',    'ICD ',    'ICU ',    'IMP ',    'ITU ',    'IPF ',    'IVF ',    'KCL ',    'LCIS ',    'LBP ',    'LLQ ',    'LUQ ',    'MCL ',    'MVP ',    'NCP ',    'NSR ',    'ORIF ',    'PCL ',    'PERRLA ',    'PFT ',    'PERRLA ',    'PCMH ',    'PMI ',    'PMS ',    'PTH ',    'PTSD ',    'PUD ',    'RDS ',    'REB ',    'RLQ ',    'ROS ',    'RUQ ',    'SAD ',    'SOB ',    'TAH ',    'THR ',    'TKR ',    'TMJ ',    'ULN ',    'URI ',    'UTI ',    'VSS ',    'XRT'
  );
  $title = strtolower(' ' . $title . ' '); //make the whole string lowercase, sometimes ucwords (below) does weird stuff if everything is caps. add spaces at the start and of the title in case there are titles that are just things like 'ADHD'
  $title = str_ireplace("-", "- ", $title);
  $title = str_ireplace(">", "> ", $title);
  $title = ucwords($title); //make all words start with an uppercase letter. including the second half of a hypenated word ie 'long-term' changes to 'Long-Term'
  $title = str_ireplace("- ", "-", $title);
  $title = str_ireplace($lowercase_words, $lowercase_words, $title); //replace all words that should be lowercase with their lowercase version
  $title = str_ireplace($medical_acronyms, $medical_acronyms, $title); //replace all medical acronyms with uppercase versions
  $title = trim($title); //gets rid of spaces at sart and end of title
  $title = ucfirst($title); //capitalizes the first letter of the first word (done in case the first word is "a" or "the" etc)
  return ($title);
}

?>
