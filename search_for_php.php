<?
	dl("php_gtk2.so");
	chdir(dirname(__FILE__));
	
	class WinMain{
		function __construct(){
			$this->db = sqlite_open("database.sqlite");
			$this->backupdir = "/home/knj/RecoverPHP";
			$this->maxsize = (1024 * 1024) * 100;
			
			$this->glade = new GladeXML("glade/win_main.glade");
			$this->glade->signal_autoconnect_instance($this);
			
			$this->window = $this->glade->get_widget("win_main");
			$this->lab_gb = $this->glade->get_widget("lab_gb");
			$this->lab_phpscripts = $this->glade->get_widget("lab_phpscripts");
			$this->lab_functions = $this->glade->get_widget("lab_functions");
			$this->lab_gladewindows = $this->glade->get_widget("lab_gladewindows");
			$this->lab_files = $this->glade->get_widget("lab_files");
			
			$this->tv_classes = $this->glade->get_widget("tv_classes");
			$this->tv_classes->set_model(
				new GtkListStore(
					Gtk::TYPE_STRING,
					Gtk::TYPE_STRING
				)
			);
			$this->tv_classes->append_column(
				new GtkTreeViewColumn("Klasse", new GtkCellRendererText(), "text", 0)
			);
			$this->tv_classes->append_column(
				new GtkTreeViewColumn("Fil", new GtkCellRendererText(), "text", 1)
			);
			$this->tv_classes->set_search_column(0);
			
			$this->tv_gladewindows = $this->glade->get_widget("tv_gladewindows");
			$this->tv_gladewindows->set_model(
				new GtkListStore(
					Gtk::TYPE_STRING,
					Gtk::TYPE_STRING
				)
			);
			$this->tv_gladewindows->append_column(
				new GtkTreeViewColumn("Vindue", new GtkCellRendererText(), "text", 0)
			);
			$this->tv_gladewindows->append_column(
				new GtkTreeViewColumn("Fil", new GtkCellRendererText(), "text", 1)
			);
			$this->tv_gladewindows->set_search_column(0);
			
			$this->tv_functions = $this->glade->get_widget("tv_functions");
			$this->tv_functions->set_model(
				new GtkListStore(
					Gtk::TYPE_STRING,
					Gtk::TYPE_STRING
				)
			);
			$this->tv_functions->append_column(
				new GtkTreeViewColumn("Funktion", new GtkCellRendererText(), "text", 0)
			);
			$this->tv_functions->append_column(
				new GtkTreeViewColumn("Fil", new GtkCellRendererText(), "text", 1)
			);
			$this->tv_functions->set_search_column(0);
			
			//Read cache.
			$this->updateCache();
			
			//Update changes to Window.
			$this->window->show_all();
		}
		
		function on_tv_classes_event($selection, $event){
			if ($event->type == 5){
				$selection = $this->tv_classes->get_selection();
				list($model, $iter) = $selection->get_selected();
				
				if ($iter && $model){
					for($i = 0; $i < count($this->tv_classes->get_columns()); $i++){
						$value = $model->get_value($iter, $i);
						
						$return[$i] = $value;
					}
				}
				
				system("kate -u /home/knj/RecoverPHP/" . $return[1] . " &");
			}
		}
		
		function on_tv_functions_event($selection, $event){
			if ($event->type == 5){
				$selection = $this->tv_functions->get_selection();
				list($model, $iter) = $selection->get_selected();
				
				if ($iter && $model){
					for($i = 0; $i < count($this->tv_classes->get_columns()); $i++){
						$value = $model->get_value($iter, $i);
						
						$return[$i] = $value;
					}
				}
				
				system("kate -u /home/knj/RecoverPHP/" . $return[1] . " &");
			}
		}
		
		function on_tv_gladewindows_event($selection, $event){
			if ($event->type == 5){
				$selection = $this->tv_gladewindows->get_selection();
				list($model, $iter) = $selection->get_selected();
				
				if ($iter && $model){
					for($i = 0; $i < count($this->tv_classes->get_columns()); $i++){
						$value = $model->get_value($iter, $i);
						
						$return[$i] = $value;
					}
				}
				
				system("kate -u /home/knj/RecoverPHP/" . $return[1] . " &");
			}
		}
		
		function on_win_main_destroy(){
			Gtk::main_quit();
		}
		
		function updateStatus(){
			$this->lab_gb->set_text(
				number_format($this->count_gb / (1024 * 1024), 2, ",", ".")
			);
			$this->lab_phpscripts->set_text(
				number_format($this->count_phpscripts, 0, ",", ".")
			);
			$this->lab_functions->set_text(
				number_format($this->count_functions, 0, ",", ".")
			);
			$this->lab_gladewindows->set_text(
				number_format($this->count_gladewindows, 0, ",", ".")
			);
			$this->lab_files->set_text(
				number_format($this->count_files, 0, ",", ".")
			);
			
			while(gtk::events_pending()){
				gtk::main_iteration();
			}
		}
		
		function updateCache(){
			$this->count_update = 0;
			$this->count_gb = 0;
			$this->count_phpscripts = 0;
			$this->count_functions = 0;
			$this->count_gladewindows = 0;
			$this->count_files = 0;
			
			$f_gf = sqlite_query($this->db, "SELECT * FROM files_read") or die(sqlite_last_error($this->db));
			while($d_gf = sqlite_fetch_array($f_gf)){
				$this->cache_file_read[$d_gf[filename]] = true;
				$this->count_gb += filesize($this->backupdir . "/" . $d_gf[filename]);
				
				$this->count_update++;
				if ($this->count_update >= 20){
					$this->count_update = 0;
					$this->updateStatus();
				}
			}
			
			$f_gc = sqlite_query($this->db, "SELECT * FROM classes_found ORDER BY class_name") or die(sqlite_last_error($this->db));
			while($d_gc = sqlite_fetch_array($f_gc)){
				$this->count_phpscripts++;
				$this->tv_classes->get_model()->append(
					array(
						$d_gc[class_name],
						$d_gc[file_name]
					)
				);
				
				$this->count_update++;
				if ($this->count_update >= 20){
					$this->count_update = 0;
					$this->updateStatus();
				}
			}
			
			$f_gf = sqlite_query($this->db, "SELECT * FROM functions_found ORDER BY function_name") or die(sqlite_last_error($this->db));
			while($d_gf = sqlite_fetch_array($f_gf)){
				$this->count_functions++;
				$this->tv_functions->get_model()->append(
					array(
						$d_gf[function_name],
						$d_gf[file_name]
					)
				);
				
				$this->count_update++;
				if ($this->count_update >= 20){
					$this->count_update = 0;
					$this->updateStatus();
				}
			}
			
			$f_ggw = sqlite_query($this->db, "SELECT * FROM gladewindows_found ORDER BY gladewindow_name") or die(sqlite_last_error($this->db));
			while($d_ggw = sqlite_fetch_array($f_ggw)){
				$this->count_gladewindows++;
				$this->tv_gladewindows->get_model()->append(
					array(
						$d_ggw[gladewindow_name],
						$d_ggw[file_name]
					)
				);
				
				$this->count_update++;
				if ($this->count_update >= 20){
					$this->count_update = 0;
					$this->updateStatus();
				}
			}
			
			$this->count_update = 0;
			$this->updateStatus();
		}
		
		function on_btn_start_clicked(){
			$this->ScanDirForPHP("/home/knj/Recover");
		}
		
		function on_btn_clearcache_clicked(){
			sqlite_query($this->db, "DELETE FROM classes_found") or die(sqlite_last_error($this->db));
			sqlite_query($this->db, "DELETE FROM files_read") or die(sqlite_last_error($this->db));
			sqlite_query($this->db, "DELETE FROM functions_found") or die(sqlite_last_error($this->db));
			sqlite_query($this->db, "DELETE FROM gladewindows_found") or die(sqlite_last_error($this->db));
			
			$this->tv_functions->get_model()->clear();
			$this->tv_gladewindows->get_model()->clear();
			$this->tv_classes->get_model()->clear();
			$this->updateCache();
		}
		
		function ScanDirForPHP($dir){
			$fp = opendir($dir);
			while(($file = readdir($fp)) !== false){
				if ($file != "." && $file != ".."){
					// && !file_exists($this->backupdir . "/" . $file)
					$fn = $dir . "/" . $file;
					
					if (!is_file($fn)){
						$this->ScanDirForPHP($fn);
					}else{
						//Update GUI.
						$this->updateStatus();
						
						$filesize = filesize($fn);
						$this->count_gb += $filesize;
						$this->count_files++;
						
						if (!$this->cache_file_read[$file] && $filesize <= $this->maxsize){
							$filecont = file_get_contents($fn);
							$found = false;
							
							if (preg_match_all("/function\s+([A-z0-9_-]+)\(/", $filecont, $matches)){
								$found = true;
								
								foreach($matches[1] AS $key => $value){
									$this->tv_functions->get_model()->append(
										array(
											$value,
											$file
										)
									);
									sqlite_query($this->db, "INSERT INTO functions_found (function_name, file_name) VALUES ('$value', '$file')") or die(sqlite_last_error($this->db));
									$this->count_functions++;
								}
							}
							
							if (preg_match_all("/<property name=\"title\" translatable=\"yes\">([\s\S]+)<\/property>/U", $filecont, $matches)){
								$found = true;
								
								foreach($matches[1] AS $key => $value){
									$this->tv_gladewindows->get_model()->append(
										array(
											$value,
											$file
										)
									);
									sqlite_query($this->db, "INSERT INTO gladewindows_found (gladewindow_name, file_name) VALUES ('$value', '$file')") or die(sqlite_last_error($this->db));
									$this->count_gladewindows++;
								}
							}
							
							if (preg_match_all("/class\s+([A-z0-9_-]+)({|\s+extends|\s+implements)/", $filecont, $matches)){
								$found = true;
								
								foreach($matches[1] AS $key => $value){
									$f2 = substr($value, 0, 2);
									$f3 = substr($value, 0, 3);
									$f4 = substr($value, 0, 4);
									$f5 = substr($value, 0, 5);
									
									if (
										//Rules to why the class should not be shown (PEAR-classes, Gtk|Gdk|Atk-classes and so on... Add your own).
										$f2 != "FF" &&
										$f2 != "Ff" &&
										$f3 != "ooi" &&
										$f3 != "Gtk" &&
										$f3 != "DB_" &&
										$f3 != "PHP" &&
										$f3 != "Gdk" &&
										$f3 != "Atk" &&
										$f3 != "PDF" &&
										$f4 != "PEAR" &&
										$f4 != "smtp" &&
										$f5 != "Pango"
									){
										$this->tv_classes->get_model()->append(
											array(
												$value,
												$file
											)
										);
										
										$this->count_phpscripts++;
										sqlite_query($this->db, "INSERT INTO classes_found (class_name, file_name) VALUES ('$value', '$file')") or die(sqlite_last_error($this->db));
									}
								}
							}
							
							if ($found){
								$write = true;
								if (!file_exists($this->backupdir . "/" . $file) || filesize($this->backupdir . "/" . $file) != filesize($fn)){
									$status = file_put_contents($this->backupdir . "/" . $file, $filecont);
									if (!$status){
										$write = false;
									}
								}
								
								//Filed have been read.
								if ($write == true){
									sqlite_query($this->db, "INSERT INTO files_read (filename) VALUES ('$file')") or die(sqlite_last_error($this->db));
								}
								
								//Free memory
								unset($f2);
								unset($f3);
								unset($f4);
								unset($f5);
								unset($write);
							}
							
							//Free memory
							unset($filecont);
							unset($status);
							unset($file);
							unset($matches);
							unset($value);
							unset($filesize);
							unset($fn);
						}
					}
				}
			}
		}
	}
	
	$win_main = new WinMain();
	Gtk::main();
?>