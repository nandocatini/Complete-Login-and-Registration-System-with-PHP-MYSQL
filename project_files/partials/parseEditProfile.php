
<?php // make this better by makin two parts of the form in the profile page when the user clicks he edit profile he gets to edit teh profile in th esema page, but previously he was not able to click or achange any of the text fields, but after clicking the edit profile they will be able to edit ( actually there will be two form -> created  within  a function,--> one will have readonly true   and then other will have read only  true --> AND THEY APPEAR ON THE condition ie if the edit btn clicked then load the form which can be edited, and if not then user is only able to read it...)--> NOT EDIT PROFILE PAGE SEPARATELY -- every thing in one 

	############################### 	for collecting the data from the databbase to show in the edit profile  page    #####################
	######################################################    if he passes the SECURITY     ##################################################

	if ( isset($_SESSION['username']) && isset($_GET['user_id']) ) {  #--> ie if the user seession is set, then only he will be able to change, it
		#$user_id = $_GET[0]; 		# --> it will give me undefined offset

		$user_id = $_GET['user_id'];# --> due to this the user can not directly come to the editpage--> well if they are loged in then they can come --> but as we are not taking their ID from the session variable (as they are only taken by the decoded value pased by the edit profile link ) so if the user is loged in and he put the address with the value ,eg. edit_profile?user_id=8=-8043209  THEN MUCH CHANCES ARE THAT HE WILL GET AFCAURCE A WRONG ID -=>
		# BUT FOR MORE SECURITY (--> if he got lucky and he found the id form our database --> now all the query are based upon this id only so carefull)WE WILL CHECK WHETHER FOR THIS ID THE USER NAME IS THE SAME OR NOT,-->(ie. the username is equal to the one whose session is set or not ! )
		$decode_id = base64_decode($user_id);
		# THERE IS AN ERROR IF WE ARE LOGED IN TO THE SITE AND  if we put a path -> giving the value in the address bar.. then IF THAT STRING WE PASSSED does not contain
		# the no 24792024278--> (WHICH IT CERTAINLY WOOULD NOT THEN ) --> in this case it would not be able to explode it with this no. so , there WILL NOT BE ANY INDEX 1 in the variable $decodedArray   ====> SO WE HAVE TO CHECK WHETHER THIS CONTAINS TEH REQUIRED STRING of--  24792024278  so if it does then only we will break it down
		# NOW IT DOESNT MATTER THAT WHETHER THIS TIME THE VALUE IN THE INDEX '1' IS CORRECT --> because in the further condition it will be checked for that and if it passes then only we collect the data  to show him the form in which the values are already put in...
		if ( strpos($decode_id, '24792024278') !== false) { # then it has this value in it --> now you can explode it  WITH NO WORRY..
			$decodedArray= explode(24792024278, $decode_id );
			$id = $decodedArray['1']; 
		}else{ # just for the sake of initializing  so that the next if condition works fine
			$id = 'yiyiuyi'; # giving strings as id will nwver be strings .. we have no's as our id's in the databaseecho $id;
		}

		if ($id === $_SESSION['id']) {
			#  ==> we are comparing the decoded id with the set session id , because if we directly perform, action on the converted id --> it can casuse hacking too..
			# as what people can do is they can copy the encrypted link of some other user and put that inside teh addresss bar, or might be ossible they can made up nay stin gand  --> by luck that can result into a valid id , that exist already into the database..

			# it means the same user is comming through the right path(ALTHOUGH WHAT THE USER CAN DO AFTER HE LOGES If he can copy that encrypted link value which is shown then he can write the path and put the value too..==> HE WILL BE ABLE TO DIRECTLY COME TO THE EDIT PROFILE PAGE WITHOUT CLICKING THE LINK)==> as in this case the encrypted id after the decode will match the value of the id which is present in the session
			try{
				$sqlQuery = "SELECT *
							FROM register.users 
							WHERE id = :id";
				$statement = $db->prepare($sqlQuery);
				$statement->execute( array(':id'=>$id) );

				if ($row = $statement->fetch()) {
					$current_username = $row['username']; # these are the current information of  the user in the database.. which we will use to show up in the edit 													profile form in the textboxes(so editable) bw the value=" HERE"
					$current_password = $row['password'];
					$current_email = $row['email'];
				}
			}catch(PDOexception $ex){#flashMessage($message,$color='red')-->returns the string   --> by default red
				echo flashMessage("something went wrong, WHILE COLLECTING THE DATA FROM THE DATABASE -->".$ex->getMessage());
			}

		}else{} # the resieved id from the url is not of this user!
	} else{} # either the session is not set --> ie he has not loged in  OR  user_id is not set, ie he has not send any information into the link




	############################################### 	for submiting the edit profile form   ##############################################
	########################################   its validation and processing to change into database   #####################################
	if ( isset($_POST['edit_sbt']) ) { # then only process the form

		$email = $_POST['email'];
		$username = $_POST['username'];# these are the information that the user wants to be saved after editing his profile
		$password = $_POST['password'];

		#------------------------------------------------		VALIDATION BEGINS 	----------------------------------------------------------
		$form_errors = array(); # to store all the array

		# errors due to empty fields
		$required_fields_array = array('email', 'username', 'password');
		$form_errors = array_merge( $form_errors, check_empty_fields($required_fields_array) );

		# error due to minimum length
		$fields_to_check_length = array('email'=>12,'username'=>4);
		$form_errors = array_merge( $form_errors,check_min_length($fields_to_check_length) );

		# error due to invalid email address
		$form_errors = array_merge($form_errors, check_email($_POST)); #  check email requires an array ie. key value pair

		#---------------------------------------------------	**	ENDS  **	------------------------------------------------------------

		#--------------------------------    after validation this data should not CLASH with another user     ------------------------------
		#---------------------------------------------- ie.	THERE IS NO ERROR IN THE FORM 		----------------------------------------------
		if ( empty($form_errors) ) {# if there was NO validation error in the form

			//checkDuplicasy($input, $columnName, $databaseName, $tableName, $db){ // $db --> PDO object we pass it because these function calls directly take them to their defination, so no file are added if we write them in the top of the utilities.php file but they will work if we include these file in the function defination

			# checking duplicasy for the email--> firstly
			$arrayReturned = checkDuplicasy_filterMe($email, 'email', 'register', 'users', $db);
			if ($arrayReturned['status'] == false ) {# ie duplicasy was NOT found for EMAIL in the database 
					
				$arrayReturned = checkDuplicasy_filterMe($username, 'username', 'register', 'users', $db);
				if ($arrayReturned['status'] == false ) {# ie duplicasy was NOT found for USARNAME too.  ==> allow him to process 
					#-------------------------------- NO DUPLICASY SO PROCESS THE FORM NOW -------------------------------------------------
					# ==========================  check if the information written in it is changed or not ==================================
					if ($current_username === $username  &&  $current_email === $email ) { # ie. nothing has been changed  -> notify that
						echo "<script>
							swal({
								title: \"NO changes made !\",
							  	text: \"to update your profile please make changes...\",
							  	timer: 3000,
							  	showConfirmButton: false
							});
						</script>";
					}# =============================================    end of checking  =====================================================
					else{#########################################		PUT DATA INTO THE TABLE 	########################################
						try{
							$sqlQuery= "UPDATE register.users
										SET username = :username, email = :email
										WHERE id = :id";
							$statement = $db->prepare($sqlQuery);
							$statement->execute( array(':username'=>$username, ':email'=>$email, ':id'=>$_SESSION['id']) );# now we will update the current users data
							if ($row = $statement->rowcount() == 1) { # ie data successfully updated
								echo popupMessage("UPDATED",'the profile has been successfully updated !', 'success', 'profile.php');
							}else{ # data was not successfully updated
								echo popupMessage("SORRY",'there was some error in updating your profile !', 'error', '#'); # ie at same page
							}
						}catch(PDOexception $ex){#flashMessage($message,$color='red')-->returns the string   --> by default red
							echo flashMessage("something went wrong, WHILE INSERTING THE DATA INTO THE DATABASE -->".$ex->getMessage());
						}#############################################    DATA INSERTION ENDS	##############################################
					}
					#----------------------------------------------	PROCESSING ENDS 	----------------------------------------------------
				
				}else{# ie duplicasy was found  for USERNAME
					# no matter what is the status is either true or exception it will show the message
					$result = flashMessage($arrayReturned['message']);# by default it is red
				}
				
			}else{# ie duplicasy was found  for EMAIL
				# no matter what is the status is either true or exception it will show the message
				$result = flashMessage($arrayReturned['message']);# by default it is red
			}

		}


	}



?>


