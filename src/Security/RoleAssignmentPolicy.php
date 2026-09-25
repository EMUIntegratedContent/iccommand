<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Decides which roles the current user may grant or revoke, and which
 * application user lists they may read.
 *
 * - ROLE_GLOBAL_ADMIN_SUPER may grant or revoke any role.
 * - ROLE_GLOBAL_ADMIN may grant or revoke any role except ROLE_GLOBAL_ADMIN_SUPER.
 * - A module admin (e.g. ROLE_MAP_ADMIN) may grant or revoke only that
 *   module's roles (ROLE_MAP_*). This is what the per-app "Manage" pages use.
 * - Everyone else may not change roles.
 *
 * ROLE_GLOBAL_ADMIN does not inherit module roles in the hierarchy, so it is
 * checked explicitly everywhere.
 */
class RoleAssignmentPolicy
{
	public const SUPER = 'ROLE_GLOBAL_ADMIN_SUPER';
	public const GLOBAL_ADMIN = 'ROLE_GLOBAL_ADMIN';
	private const BASE_ROLE = 'ROLE_USER';

	/** @var string[] every role defined in the role hierarchy */
	private array $knownRoles;

	/**
	 * @param array<string, string[]> $roleHierarchy the security.role_hierarchy.roles parameter
	 */
	public function __construct(
		private AuthorizationCheckerInterface $authorizationChecker,
		#[Autowire(param: 'security.role_hierarchy.roles')]
		array $roleHierarchy,
	) {
		$roles = array_keys($roleHierarchy);
		foreach ($roleHierarchy as $inherited) {
			foreach ((array) $inherited as $role) {
				$roles[] = $role;
			}
		}
		$this->knownRoles = array_values(array_unique($roles));
	}

	public function isGlobalAdmin(): bool
	{
		return $this->authorizationChecker->isGranted(self::GLOBAL_ADMIN);
	}

	public function isKnownRole(string $role): bool
	{
		return in_array($role, $this->knownRoles, true);
	}

	/**
	 * Whether the current user may grant or revoke the given role.
	 */
	public function canAssign(string $role): bool
	{
		if ($role === self::SUPER) {
			return $this->authorizationChecker->isGranted(self::SUPER);
		}
		if ($this->isGlobalAdmin()) {
			return true;
		}
		$prefix = self::modulePrefix($role);

		return $prefix !== null && $this->canManagePrefix($prefix);
	}

	/**
	 * Whether the current user administers the module with this role prefix
	 * (e.g. "ROLE_MAP_"). Global admins administer every module.
	 */
	public function canManagePrefix(string $prefix): bool
	{
		if (!self::isValidPrefix($prefix)) {
			return false;
		}
		if ($this->isGlobalAdmin()) {
			return true;
		}
		if ($prefix === 'ROLE_GLOBAL_') {
			return false;
		}

		return $this->authorizationChecker->isGranted($prefix . 'ADMIN');
	}

	/**
	 * Whether the current user administers at least one module.
	 */
	public function isAnyAdmin(): bool
	{
		if ($this->isGlobalAdmin()) {
			return true;
		}
		foreach ($this->knownRoles as $role) {
			if (preg_match('/^ROLE_[A-Z]+_ADMIN$/', $role) && $role !== self::GLOBAL_ADMIN
				&& $this->authorizationChecker->isGranted($role)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Roles that would be added and removed, ignoring ROLE_USER, which every
	 * user has implicitly.
	 *
	 * @param string[] $current
	 * @param string[] $requested
	 * @return array{added: string[], removed: string[]}
	 */
	public static function diff(array $current, array $requested): array
	{
		$normalize = static fn (array $roles): array => array_values(array_unique(array_filter(
			array_map('strval', $roles),
			static fn (string $r): bool => $r !== self::BASE_ROLE && $r !== ''
		)));
		$current = $normalize($current);
		$requested = $normalize($requested);

		return [
			'added' => array_values(array_diff($requested, $current)),
			'removed' => array_values(array_diff($current, $requested)),
		];
	}

	public static function isValidPrefix(string $prefix): bool
	{
		return (bool) preg_match('/^ROLE_[A-Z]+_$/', $prefix);
	}

	/**
	 * "ROLE_MAP_EDIT" => "ROLE_MAP_"; null if the role has no module prefix.
	 */
	public static function modulePrefix(string $role): ?string
	{
		return preg_match('/^(ROLE_[A-Z]+_)[A-Z_]+$/', $role, $m) ? $m[1] : null;
	}
}
