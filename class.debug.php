<?php
/**
 *  DEBUG
 *
 */

class Debug 
{           
  var $INFO = array();
  
  var $EVENTS = array();
  
  var $FILES = array();
  var $TYPES = array();
      
  var $init = 0;
  
  var $currentFileDefault = '__global__';
  var $currentFile = FALSE;
  
  // -- thrifty mode; 
  // NO: SQL, Varables & SCRIPTS
  
  var $ACTIVE = FALSE;
    
  function __construct()
  {
    $this->init = time();
  }
    
  function on()
  {
    $this->ACTIVE = TRUE;
  }
  
  function off()
  {
    $this->ACTIVE = FALSE;
  }  
    
  function file($currentFile = FALSE)
  {  
    $this->currentFile = $currentFile;    
  }
  
  function add($msg, $type = "msg", $file = FALSE)
  {
    if ($msg === '') return;
            
    list($m,$s) = explode(" ",microtime());
    
    if (preg_match("/<script/",$msg))
    {
      $type = 'SCR';
    }
    
    $type = strtoupper($type);
    
    if ($type == 'SCR')
    {
      if (!$this->ACTIVE) return;
      
      $msg = htmlspecialchars($msg);
    }    
    
    $i = count($this->INFO);
    
    if (!$file)
    {
      $file = $this->currentFile;
    }
    
    $this->EVENTS[$i] = $m;  
    $this->FILES[$i]  = $file;
    
    $this->INFO[]     = $msg;
    $this->TYPES[$i]  = $type;                
  }
  
  function err($msg,$file = FALSE)
  {
    $this->add($msg,"err",$file);
  }
  
  function msg($msg,$file = FALSE)
  {
    $this->add($msg,"msg",$file);
  }
  
  function qry($msg,$row = array(),$file = FALSE)
  {
    if (!$this->ACTIVE) return;
    
    if (is_object($row))
    {
      $row = array($row);
    }
    
    $params = array();
    
    if (is_array($row))
    {
      foreach ($row as $k => $v)
      {
        $params[] = sprintf('%s = %s', $k, str_replace("\n"," || ",$v)); 
      }
      
      if (count($params) > 0)
      {
        $msg .= "\n" . implode("\n", $params);
      }           
    }
        
    $this->add($msg,"QRY",$file);    
  }
  
  function addVar($varname, $value)
  {
    if (!$this->ACTIVE) return;
    
    if (is_bool($value))
    {
      $result = ($value) ? 'TRUE' : 'FALSE'; 
      $this->add($varname . " = " . $result);
    }
    else if (is_string($value) || is_numeric($value))
    {
      $this->add($varname . " = " .$value);
    }
    else
    {
      $this->add($varname . " = \n" . var_export($value,TRUE) ,"var");
    }           
  }
  
  function log($DIR = "./log/",$file = FALSE)
  {
    if (!$file)
    {
      $file = sprintf("debug-output-%s.htm",date("YmdHis"));
    }
    
    $result = '<!DOCTYPE html><html lang="cs"><head><meta charset="UTF-8"></head><body>' . $this->parseOutput() . '</body></html>';
                
    file_put_contents($DIR . $file, $result, FILE_APPEND | LOCK_EX);                
  }
  
  function output($SHOW = FALSE)
  {
    if (!$SHOW) return;
    
    echo $this->parseOutput();
  }
      
  function parseOutput()
  {            
    ob_start(); 
    
    echo '<div class="debug">';
    echo '<h1>&gt; DEBUG ' . $this->init . '</h1>'; 
    
    $prev = FALSE;
    $files = array();
    $cfile = 0;
        
    foreach ($this->INFO as $i => $msg)
    {
      if ($prev != $this->FILES[$i])
      {
        $cfile = count($files);
        $files[$cfile] = $this->FILES[$i];                
      }
                  
      $type = strtoupper($this->TYPES[$i]);
      
      $tmp = explode("\n",$msg);
      $class = $type;
      
      if (count($tmp) > 1)
      {
        $msg = $tmp[0] . '</div><pre>';
        unset($tmp[0]);
        $msg .= implode("\n",$tmp);
        $msg .= '</pre>';
        
        $class .= ' wdetail';        
      }
      else
      {
        $msg .= '</div>';
      }
                        
      printf("<div class=\"%s\"><div onclick=\"gravdept_toggle(this); return false;\"><span>%s[%s][%s]</span> %s</div>",$class,$this->EVENTS[$i],str_pad($cfile,2,'0',0),$type,$msg);
      
      $prev = $this->FILES[$i];   
    }
                  
    echo str_pad("","80","-")."\n";
    echo '<pre>';
    
    foreach ($files as $i => $file)
    {
      printf("[%s] %s\n",str_pad($i,2,'0',0),$file);
    }  
        
    echo '</pre>';
    echo '</div>';    
    ?>
<style type="text/css">    
/**
 *  DEBUGGER
 */

.debug h1 { font-size: 1.1em; }

.debug pre { margin-top: 0.2em; margin-bottom: 0.2em; }

.wdetail pre { display: none; margin: 0.2em 1.6em; }
.wdetail div { display: inline-block; position: relative; padding-right: 3em; cursor: pointer;  }
.wdetail div:after 
{
   content: "[...]";
   display: block;
   position: absolute; 
   right: 0;
   top: 0;
   color: grey;   
}

.debug .ERR { color: red; }
.debug .SCR { color: #f7d21d; }
.debug .VAR { color: #78e2f3; }
.debug .QRY { color: #70f11f; }
.debug .MSG { font-weight: bold; }

.debug span { color: grey; font-weight: normal; }
 
.debug 
{ 
   font-family: monospace;
   background-color: #f5f5f5;
   padding: 1em;
   font-size: .85em;
   background: #000; 
   color: #fff;  
}     
</style>
<script>
function gravdept_next(elem) {
    do {
        elem = elem.nextSibling;
    } while (elem && elem.nodeType != 1);
    return elem;                
}

function gravdept_toggle(elem) {
    var nextElem = gravdept_next(elem);
	
    if (nextElem.style.display==='block') { nextElem.style.display='none'; }
    else { nextElem.style.display='block'; }
}
</script>    
    
    <?php        
    return ob_get_clean();     
  }        
}
