<?php
include_once(GALAXIA_LIBRARY.'/managers/base.php');
//!! ProcessManager
//! A class to maniplate processes.
/*!
  This class is used to add,remove,modify and list
  processes.
*/
class ProcessManager extends BaseManager
{
    public $parser;
    public $tree;
    public $current;
    public $buffer = '';

    /*!
    Creates an XML representation of a process.
    */
    function serialize_process($pId)
    {
        // <process>
        $out = '<process>'."\n";
        $proc_info = $this->get_process($pId);
        $procname = $proc_info['normalized_name'];
        $out.= '  <name>'.htmlspecialchars($proc_info['name']).'</name>'."\n";
        $out.= '  <isValid>'.htmlspecialchars($proc_info['isValid']).'</isValid>'."\n";
        $out.= '  <version>'.htmlspecialchars($proc_info['version']).'</version>'."\n";
        $out.= '  <isActive>'.htmlspecialchars($proc_info['isActive']).'</isActive>'."\n";
        $out.='   <description>'.htmlspecialchars($proc_info['description']).'</description>'."\n";
        $out.= '  <lastModif>'.date("d/m/Y [h:i:s]",$proc_info['lastModif']).'</lastModif>'."\n";
        $out.= '  <sharedCode><![CDATA[';
        $fp=fopen(GALAXIA_PROCESSES."/$procname/code/shared.php","r");
        while(!feof($fp)) {
            $line=fread($fp,8192);
            $out.=$line;
        }
        fclose($fp);
        $out.= '  ]]></sharedCode>'."\n";
        // Now loop over activities
        $query = "select * from ".self::tbl('activities')."where pId= ?";
        $result = $this->query($query,array($pId));
        $out.='  <activities>'."\n";
        $am = new ActivityManager($this->db);
        while($res = $result->fetchRow()) {
            $name = $res['normalized_name'];
            $out.='    <activity>'."\n";
            $out.='      <name>'.htmlspecialchars($res['name']).'</name>'."\n";
            $out.='      <type>'.htmlspecialchars($res['type']).'</type>'."\n";
            $out.='      <description>'.htmlspecialchars($res['description']).'</description>'."\n";
            $out.='      <lastModif>'.date("d/m/Y [h:i:s]",$res['lastModif']).'</lastModif>'."\n";
            $out.='      <isInteractive>'.$res['isInteractive'].'</isInteractive>'."\n";
            $out.='      <isAutoRouted>'.$res['isAutoRouted'].'</isAutoRouted>'."\n";
            $out.='      <roles>'."\n";

            $roles = $am->get_activity_roles($res['activityId']);
            foreach($roles as $role) {
                $out.='        <role>'.htmlspecialchars($role['name']).'</role>'."\n";
            }
            $out.='      </roles>'."\n";
            $out.='      <code><![CDATA[';
            $fp=fopen(GALAXIA_PROCESSES."/$procname/code/activities/$name.php","r");
            while(!feof($fp)) {
                $line=fread($fp,8192);
                $out.=$line;
            }
            fclose($fp);
            $out.='      ]]></code>';
            if($res['isInteractive']=='y') {
                $out.='      <template><![CDATA[';
                $fp=fopen(GALAXIA_PROCESSES."/$procname/code/templates/$name.tpl","r");
                while(!feof($fp)) {
                    $line=fread($fp,8192);
                    $out.=$line;
                }
                fclose($fp);
                $out.='      ]]></template>';
            }
            $out.='    </activity>'."\n";
        }
        $out.='  </activities>'."\n";
        $out.='  <transitions>'."\n";
        $transitions = $am->get_process_transitions($pId);
        foreach($transitions as $tran) {
            $out.='     <transition>'."\n";
            $out.='       <from>'.htmlspecialchars($tran['actFromName']).'</from>'."\n";
            $out.='       <to>'.htmlspecialchars($tran['actToName']).'</to>'."\n";
            $out.='     </transition>'."\n";
        }
        $out.='  </transitions>'."\n";
        $out.= '</process>'."\n";
        //$fp = fopen(GALAXIA_PROCESSES."/$procname/$procname.xml","w");
        //fwrite($fp,$out);
        //fclose($fp);
        return $out;
    }

    /**
     * Creates  a process PHP data structure from its XML
     * representation
     *
    **/
    function unserialize_process($xml)
    {
        // Create SAX parser assign this object as base for handlers
        // handlers are private methods defined below.
        // keep contexts and parse
        $this->parser = xml_parser_create();
        xml_parser_set_option($this->parser,XML_OPTION_CASE_FOLDING,0);
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, "_start_element_handler", "_end_element_handler");
        xml_set_character_data_handler($this->parser, "_data_handler");
        $aux=Array(
          'name'=>'root',
          'children'=>Array(),
          'parent' => 0,
          'data'=>''
        );
        $this->tree[0]=$aux;
        $this->current=0;
        if (!xml_parse($this->parser, $xml, true)) {
           $error = sprintf("XML error: %s at line %d",
                        xml_error_string(xml_get_error_code($this->parser)),
                        xml_get_current_line_number($this->parser));
           trigger_error($error,E_USER_WARNING);
        }
        xml_parser_free($this->parser);
        // Now that we have the tree we can do interesting things
        //print_r($this->tree);
        $process=Array();
        $activities=Array();
        $transitions=Array();
        for($i=0;$i<count($this->tree[1]['children']);$i++) {
          // Process attributes
          $z=$this->tree[1]['children'][$i];
          $name = trim($this->tree[$z]['name']);
          if($name=='activities') {
            for($j=0;$j<count($this->tree[$z]['children']);$j++) {
              $z2 = $this->tree[$z]['children'][$j];
              // this is an activity $name = $this->tree[$z2]['name'];
              if($this->tree[$z2]['name']=='activity') {
                for($k=0;$k<count($this->tree[$z2]['children']);$k++) {
                  $z3 = $this->tree[$z2]['children'][$k];
                  $name = trim($this->tree[$z3]['name']);
                  $value= trim($this->tree[$z3]['data']);
                  if($name=='roles') {
                    $roles=Array();
                    for($l=0;$l<count($this->tree[$z3]['children']);$l++) {
                      $z4 = $this->tree[$z3]['children'][$l];
                      $name = trim($this->tree[$z4]['name']);
                      $data = trim($this->tree[$z4]['data']);
                      $roles[]=$data;
                    }
                  } else {
                    $aux[$name]=$value;
                    //print("$name:$value<br />");
                  }
                }
                $aux['roles']=$roles;
                $activities[]=$aux;
              }
            }
          } elseif($name=='transitions') {
            for($j=0;$j<count($this->tree[$z]['children']);$j++) {
              $z2 = $this->tree[$z]['children'][$j];
              // this is an activity $name = $this->tree[$z2]['name'];
              if($this->tree[$z2]['name']=='transition') {
                for($k=0;$k<count($this->tree[$z2]['children']);$k++) {
                  $z3 = $this->tree[$z2]['children'][$k];
                  $name = trim($this->tree[$z3]['name']);
                  $value= trim($this->tree[$z3]['data']);
                  if($name == 'from' || $name == 'to') {
                    $aux[$name]=$value;
                  }
                }
              }
              $transitions[] = $aux;
            }
          } else {
            $value = trim($this->tree[$z]['data']);
            //print("$name is $value<br />");
            $process[$name]=$value;
          }
        }
        $process['activities']=$activities;
        $process['transitions']=$transitions;
        return $process;
    }

    /**
     * Creates a process from the process data structure, if you want to
     * convert an XML to a process then use first unserialize_process
     * and then this method.
     *
    **/
    function import_process($data)
    {
        //Now the show begins
        $am = new ActivityManager($this->db);
        $rm = new RoleManager($this->db);
        // First create the process
        $vars = Array(
            'name'          => $data['name'],
            'version'       => $data['version'],
            'description'   => $data['description'],
            'lastModif'     => $data['lastModif'],
            'isActive'      => $data['isActive'],
            'isValid'       => $data['isValid']
        );

        $pid = $this->replace_process(0,$vars,false);
        //Put the shared code
        $process = new Process($pid);
        $procname = $process->getNormalizedName();

        $fp = fopen(GALAXIA_PROCESSES."/$procname/code/shared.php","w");
        fwrite($fp,$data['sharedCode']);
        fclose($fp);
        $actids = Array();
        // Foreach activity create activities
        foreach($data['activities'] as $activity) {
            $vars = Array(
                'name' => $activity['name'],
                'description' => $activity['description'],
                'type' => $activity['type'],
                'lastModif' => $activity['lastModif'],
                'isInteractive' => $activity['isInteractive'],
                'isAutoRouted' => $activity['isAutoRouted']
            );
            $actname=ActivityManager::normalize($activity['name']);

            $actid = $am->replace_activity($pid,0,$vars);
            $fp = fopen(GALAXIA_PROCESSES."/$procname/code/activities/$actname".'.php',"w");
            fwrite($fp,$activity['code']);
            fclose($fp);
            if($activity['isInteractive']=='y') {
                $fp = fopen(GALAXIA_PROCESSES."/$procname/code/templates/$actname".'.tpl',"w");
                fwrite($fp,$activity['template']);
                fclose($fp);
            }
            $actids[$activity['name']] = $am->_get_activity_id_by_name($pid,$activity['name']);
            $actname = ActivityManager::normalize($activity['name']);
            $now = date("U");

            foreach($activity['roles'] as $role) {
                $vars = Array(
                    'name' => $role,
                    'description' => $role,
                    'lastModif' => $now,
                );
                if(!$rm->role_name_exists($pid,$role)) {
                    $rid=$rm->replace_role($pid,0,$vars);
                } else {
                    $rid = $rm->get_role_id($pid,$role);
                }
                if($actid && $rid) {
                    $act = $am->getActivity($actid);
                    $act->addRole($rid);
                }
            }
        }
        foreach($data['transitions'] as $tran) {
            $am->add_transition($pid,$actids[$tran['from']],$actids[$tran['to']]);
        }
        // FIXME: recompile activities seems to be needed here
        foreach ($actids as $name => $actid) {
            $am->compile_activity($pid,$actid);
        }
        // create a graph for the new process
        $am->build_process_graph($pid);
        unset($am);
        unset($rm);
        $msg = sprintf(tra('Process %s %s imported'),$process->getName(),$process->getVersion());
        $this->notify_all(2,$msg);
    }

  /*!
   Creates a new process based on an existing process
   changing the process version. By default the process
   is created as an unactive process and the version is
   by default a minor version of the process.
   */
  ///\todo copy process activities and so
  function new_process_version($pId, $minor=true)
  {
      $oldpid = $pId;
      $oldProcess = new Process($oldpid);

      $proc_info = $this->get_process($pId);
      $name = $oldProcess->getName();

      if(!$proc_info) return false;
      // Now update the version
      $version = $this->_new_version($oldProcess->getVersion(),$minor);
      while(Process::exists($name,$version)) {
          $version = $this->_new_version($version,$minor);
      }
      // Make new versions unactive
      $proc_info['version'] = $version;
      $proc_info['isActive'] = 'n';

      // create a new process, but don't create start/end activities
      $pid = $this->replace_process(0, $proc_info, false);
      $newProcess = new Process($pid);

      // And here copy all the activities & so
      $am = new ActivityManager($this->db);
      $query = "select * from ".self::tbl('activities')." where pId=?";
      $result = $this->query($query,array($oldpid));
      $newaid = array();
      while($res = $result->fetchRow()) {
          $oldaid = $res['activityId'];
          $newaid[$oldaid] = $am->replace_activity($pid,0,$res);
      }
      // create transitions
      $query = "select * from ".self::tbl('transitions')." where pId=?";
      $result = $this->query($query,array($oldpid));
      while($res = $result->fetchRow()) {
          if (empty($newaid[$res['actFromId']]) || empty($newaid[$res['actToId']])) {
              continue;
          }
          $am->add_transition($pid,$newaid[$res['actFromId']],$newaid[$res['actToId']]);
      }

      // create roles
      $rm = new RoleManager($this->db);
      $query = "select * from ".self::tbl('roles')."where pId=?";
      $result = $this->query($query,array($oldpid));
      $newrid = array();
      while($res = $result->fetchRow()) {
          if(!$rm->role_name_exists($pid,$res['name'])) {
              $rid=$rm->replace_role($pid,0,$res);
          } else {
              $rid = $rm->get_role_id($pid,$res['name']);
          }
          $newrid[$res['roleId']] = $rid;
      }
      // map users to roles
      if (count($newrid) > 0) {
          $query = "select * from ".self::tbl('user_roles')."where pId=?";
          $result = $this->query($query,array($oldpid));
          while($res = $result->fetchRow()) {
              if (empty($newrid[$res['roleId']])) {
                  continue;
              }
              $rm->map_user_to_role($pid,$res['user'],$newrid[$res['roleId']]);
          }
      }
      // add roles to activities
      if (count($newaid) > 0 && count($newrid ) > 0) {
          $bindMarkers = '?' . str_repeat(', ?',count($newaid) -1);
          $query = "select * from ".self::tbl('activity_roles')." where activityId in ($bindMarkers)";
          $result = $this->query($query,array_keys($newaid));
          while($res = $result->fetchRow())
          {
              if (empty($newaid[$res['activityId']]) || empty($newrid[$res['roleId']]))
              {
                  continue;
              }
              $act = $am->getActivity($newaid[$res['activityId']]);
              $act->addRole($newrid[$res['roleId']]);
          }
      }

      //Now since we are copying a process we should copy
      //the old directory structure to the new directory
      $oldname = $oldProcess->getNormalizedName();
      $newname = $newProcess->getNormalizedName();
      $this->_rec_copy(GALAXIA_PROCESSES."/$oldname",GALAXIA_PROCESSES."/$newname");

      // create a graph for the new process
      $am->build_process_graph($pid);
      return $pid;
  }

  /*!
    Gets a process by pId. Fields are returned as an asociative array
  */
  function get_process($pId)
  {
    $query = "select * from ".self::tbl('processes')." where pId=?";
    $result = $this->query($query,array($pId));
    if(!$result->numRows()) return false;
    $res = $result->fetchRow();
    return $res;
  }

  /*!
   Lists processes (all processes)
  */
  function list_processes($offset,$maxRecords,$sort_mode,$find,$where='')
  {
    $sort_mode = $this->convert_sortmode($sort_mode);
    if($find) {
      $findesc = '%'.$find.'%';
      $mid=" where ((name like ?) or (description like ?))";
      $bindvars = array($findesc,$findesc);
    } else {
      $mid="";
      $bindvars = array();
    }
    if($where) {
      if($mid) {
        $mid.= " and ($where) ";
      } else {
        $mid.= " where ($where) ";
      }
    }
    $query = "select * from ".self::tbl('processes')." $mid order by $sort_mode";
    $query_cant = "select count(*) from ".self::tbl('processes')." $mid";
    $result = $this->query($query,$bindvars,$maxRecords,$offset);
    $cant = $this->getOne($query_cant,$bindvars);
    $ret = Array();
    while($res = $result->fetchRow()) {
      $ret[] = $res;
    }
    $retval = Array();
    $retval["data"] = $ret;
    $retval["cant"] = $cant;
    return $retval;
  }


  /*!
    Removes a process by pId
  */
  function remove_process($pId)
  {
      $process = new Process($pId);
      $process->deactivate();
      $name = $process->getNormalizedName();

      $aM = new ActivityManager($this->db);
      // Remove process activities
      $query = "select activityId from ".self::tbl('activities')." where pId=?";
      $result = $this->query($query,array($pId));
      while($res = $result->fetchRow()) {
          $aM->remove_activity($pId,$res['activityId']);
      }

      // Remove process roles
      $query = "delete from ".self::tbl('roles')." where pId=?";
      $this->query($query,array($pId));
      $query = "delete from ".self::tbl('user_roles')." where pId=?";
      $this->query($query,array($pId));

      // Remove the directory structure
      if (!empty($name) && is_dir(GALAXIA_PROCESSES."/$name")) {
          $this->_remove_directory(GALAXIA_PROCESSES."/$name",true);
      }
      if (GALAXIA_TEMPLATES && !empty($name) && is_dir(GALAXIA_TEMPLATES."/$name")) {
          $this->_remove_directory(GALAXIA_TEMPLATES."/$name",true);
      }
      // And finally remove the proc
      $query = "delete from ".self::tbl('processes')." where pId=?";
      $this->query($query,array($pId));
      $msg = sprintf(tra('Process %s removed'),$name);
      $this->notify_all(5,$msg);

      return true;
  }

  /**
   * Updates or inserts a new process in the database, $vars is an asociative
   * array containing the fields to update or to insert as needed.
   *
   * @param integer $pId is the processId
   *
  **/
  function replace_process($pId, $vars, $create = true)
  {
      $TABLE_NAME = self::tbl('processes');
      $now = date("U");
      $vars['lastModif']=$now;
      $vars['normalized_name'] = Process::normalize($vars['name'],$vars['version']);

      if($pId) {
          // update mode
          $oldProcess = new Process($pId);
          $first = true;
          $bindvars = array();
          $query ="update $TABLE_NAME set";
          foreach($vars as $key=>$value) {
              if(!$first) $query.= ',';
              $query.= " $key=? ";
              $bindvars[] = $value;
              $first = false;
          }
          $query .= " where pId=? ";
          $bindvars[] = $pId;
          $this->query($query,$bindvars);
          // Note that if the name is being changed then
          // the directory has to be renamed!
          $oldname = $oldProcess->getNormalizedName();
          $newname = $vars['normalized_name'];
          if ($newname != $oldname) {
              rename(GALAXIA_PROCESSES."/$oldname",GALAXIA_PROCESSES."/$newname");
          }
          $msg = sprintf(tra('Process %s has been updated'),$vars['name']);
          $this->notify_all(3,$msg);
      } else {
          unset($vars['pId']);
          // insert mode
          $name = Process::normalize($vars['name'],$vars['version']);
          $this->_create_directory_structure($name);
          $first = true;
          $query = "insert into $TABLE_NAME(";
          foreach(array_keys($vars) as $key) {
              if(!$first) $query.= ',';
              $query.= "$key";
              $first = false;
          }
          $query .=") values(";
          $first = true;
          $bindvars = array();
          foreach(array_values($vars) as $value) {
              if(!$first) $query.= ',';
              $query.= "?";
              $bindvars[] = $value;
              $first = false;
          }
          $query .=")";
          $this->query($query,$bindvars);
          $pId = $this->getOne("select max(pId) from $TABLE_NAME where lastModif=?",array($now));
          // Now automatically add a start and end activity
          // unless importing ($create = false)
          if($create) {
              $aM= new ActivityManager($this->db);
              $vars1 = Array(
                  'name' => 'start',
                  'description' => 'default start activity',
                  'type' => 'start',
                  'isInteractive' => 'y',
                  'isAutoRouted' => 'y'
                  );
              $vars2 = Array(
                  'name' => 'end',
                  'description' => 'default end activity',
                  'type' => 'end',
                  'isInteractive' => 'n',
                  'isAutoRouted' => 'y'
                  );
              $aM->replace_activity($pId,0,$vars1);
              $aM->replace_activity($pId,0,$vars2);
          }
          $msg = sprintf(tra('Process %s has been created'),$vars['name']);
          $this->notify_all(4,$msg);
      }
      // Get the id
      return $pId;
}

  /*!
   \private
   Generates a new minor version number
  */
  function _new_version($version,$minor=true)
  {
    $parts = explode('.',$version);
    if($minor) {
      $parts[count($parts)-1]++;
    } else {
      $parts[0]++;
      for ($i = 1; $i < count($parts); $i++) {
        $parts[$i] = 0;
      }
    }
    return implode('.',$parts);
  }

  /*!
   \private
   Creates directory structure for process
  */
  function _create_directory_structure($name)
  {
    // Create in processes a directory with this name
    mkdir(GALAXIA_PROCESSES."/$name",0770);
    mkdir(GALAXIA_PROCESSES."/$name/graph",0770);
    mkdir(GALAXIA_PROCESSES."/$name/code",0770);
    mkdir(GALAXIA_PROCESSES."/$name/compiled",0770);
    mkdir(GALAXIA_PROCESSES."/$name/code/activities",0770);
    mkdir(GALAXIA_PROCESSES."/$name/code/templates",0770);
    if (GALAXIA_TEMPLATES) {
      mkdir(GALAXIA_TEMPLATES."/$name",0770);
    }
    // Create shared file
    $fp = fopen(GALAXIA_PROCESSES."/$name/code/shared.php","w");
    fwrite($fp,'<'.'?'.'php'."\n".'?'.'>');
    fclose($fp);
  }

  /*!
   \private
   Removes a directory recursively
  */
  function _remove_directory($dir,$rec=false)
  {
    // Prevent a disaster
    if(trim($dir) == '/'|| trim($dir)=='.' || trim($dir)=='templates' || trim($dir)=='templates/') return false;
    $h = opendir($dir);
    while(($file = readdir($h)) != false) {
      if(is_file($dir.'/'.$file)) {
        @unlink($dir.'/'.$file);
      } else {
        if($rec && $file != '.' && $file != '..') {
          $this->_remove_directory($dir.'/'.$file, true);
        }
      }
    }
    closedir($h);
    @rmdir($dir);
    @unlink($dir);
  }

  function _rec_copy($dir1,$dir2)
  {
    @mkdir($dir2,0777);
    $h = opendir($dir1);
    while(($file = readdir($h)) !== false) {
      if(is_file($dir1.'/'.$file)) {
        copy($dir1.'/'.$file,$dir2.'/'.$file);
      } else {
        if($file != '.' && $file != '..') {
          $this->_rec_copy($dir1.'/'.$file, $dir2.'/'.$file);
        }
      }
    }
    closedir($h);
  }

  function _start_element_handler($parser,$element,$attribs)
  {
    $aux=Array('name'=>$element,
               'data'=>'',
               'parent' => $this->current,
               'children'=>Array());
    $i = count($this->tree);
    $this->tree[$i] = $aux;

    $this->tree[$this->current]['children'][]=$i;
    $this->current=$i;
  }


  function _end_element_handler($parser,$element)
  {
    //when a tag ends put text
    $this->tree[$this->current]['data']=$this->buffer;
    $this->buffer='';
    $this->current=$this->tree[$this->current]['parent'];
  }


  function _data_handler($parser,$data)
  {
    $this->buffer.=$data;
  }

}


?>