<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use InvalidArgumentException;
use Roddy\FirestoreEloquent\Firestore\Eloquent\DataHelperController;

/** Laravel authentication provider backed by a Firestore model. */
final class FirestoreUserProvider implements UserProvider
{
    /** @param class-string $model */
    public function __construct(
        private readonly Hasher $hasher,
        private readonly string $model,
        private readonly string $passwordField = 'password'
    ) {
        if (!class_exists($model)) {
            throw new InvalidArgumentException("Firestore authentication model [{$model}] does not exist.");
        }
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->asAuthenticatable($this->model::find($identifier));
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $user = $this->retrieveById($identifier);
        if (!$user || !$user->getRememberToken()) {
            return null;
        }

        return hash_equals((string) $user->getRememberToken(), (string) $token) ? $user : null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        if (!$user instanceof DataHelperController) {
            throw new InvalidArgumentException('The Firestore user must be a Firestore model result.');
        }

        $user->persistRememberToken((string) $token);
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $filters = [];
        foreach ($credentials as $key => $value) {
            if ($key === $this->passwordField || str_contains((string) $key, 'password') || $value instanceof \Closure) {
                continue;
            }
            $filters[] = [(string) $key, '=', $value];
        }

        if ($filters === []) {
            return null;
        }

        return $this->asAuthenticatable(
            $this->model::where(count($filters) === 1 ? $filters[0] : $filters)->first()
        );
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials[$this->passwordField] ?? null;
        $hashed = $user->getAuthPassword();

        return is_string($plain) && is_string($hashed) && $hashed !== ''
            && $this->hasher->check($plain, $hashed);
    }

    /** Supported by Laravel 11+ and harmless on earlier supported versions. */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        $plain = $credentials[$this->passwordField] ?? null;
        if (!is_string($plain) || (!$force && !$this->hasher->needsRehash($user->getAuthPassword()))) {
            return;
        }

        if ($user instanceof DataHelperController) {
            $user->update([$this->passwordField => $this->hasher->make($plain)]);
        }
    }

    private function asAuthenticatable(mixed $user): ?Authenticatable
    {
        if ($user === null) {
            return null;
        }

        if (!$user instanceof Authenticatable) {
            throw new InvalidArgumentException('Firestore authentication queries must return an Authenticatable model result.');
        }

        return $user;
    }
}
