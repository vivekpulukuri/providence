<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Auth/IAuthAdapter.php : interface for authentication adapters
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Auth
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

interface IAuthAdapter {

	/**
	 * Authenticates user
	 *
	 * @param string $ps_username user name
	 * @param string $ps_password cleartext password
	 * @param null $pa_options Associative array of options
	 * @return boolean
	 */
	public static function authenticate($ps_username, $ps_password="", $pa_options=null);

	/**
	 * Creates new user. Should throw AuthClassFeatureException if not implemented. Note that while this is called when a new
	 * user is created in CollectiveAccess, it can be used to verify that the given credentials already exist in
	 * the backend in question. You could, for instance, use this to check group membership or other access restrictions. If
	 * you want the CollectiveAccess insert() process to fail, throw an exception other than AuthClassFeatureException. Otherwise
	 * the corresponding table record in ca_users will be created.
	 *
	 * @param string $ps_username user name
	 * @param string $ps_password cleartext password
	 * @return string|null The password to store in the ca_users table. Can be left empty for
	 * back-ends where it doesn't make any sense to store a password locally (e.g. LDAP or OAuth).
	 */
	public static function createUser($ps_username, $ps_password);

	/**
	 * Deletes user. Should throw AuthClassFeatureException if not implemented.
	 *
	 * @param string $ps_username user name
	 * @return bool delete successful or not?
	 */
	public static function deleteUser($ps_username);

	/**
	 * Updates password for existing user. Should throw AuthClassFeatureException if not implemented.
	 *
	 * @param string $ps_username user name
	 * @param string $ps_password cleartext password
	 * @return string|null The password to store in the ca_users table. Can be left empty for
	 * back-ends where it doesn't make any sense to store a password locally (e.g. LDAP or OAuth).
	 */
	public static function updatePassword($ps_username, $ps_password);


	/**
	 * Indicates whether this Adapter supports updating passwords programmatically. If it doesn't,
	 * CollectiveAccess' own password reset mechanism will be disabled for this Adapter
	 *
	 * @return bool
	 */
	public static function supportsPasswordUpdate();

}

class AuthClassFeatureException extends Exception {}
