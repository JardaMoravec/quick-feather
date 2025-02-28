<?php

namespace QuickFeather;

use Entity\User\User\Email;
use Entity\User\User\Phone;
use Entity\User\User\UserId;
use QuickFeather\EntityManager\Type\Complex\String\String30;
use Entity\Base\Role\Role;


class Current extends UserId {

	/**
	 * @param int $id
	 * @param String30 $nick
	 * @param Email $email
	 * @param Phone $phone
	 * @param Role $role
	 * @param bool $isRealUser
	 * @param bool $publicPhone
	 * @param bool $isStore
	 */
	public function __construct(
		public int               $id,
		public readonly String30 $nick,
		public readonly Email    $email,
		public readonly Phone    $phone,
		public readonly Role     $role,
		public readonly bool     $isRealUser,
		public readonly bool     $publicPhone,
		public readonly bool     $isStore,
	) {
		parent::__construct($id);
	}

	/**
	 * @return Current
	 */
	public function jsonSerialize(): Current {
		return $this;
	}
}
