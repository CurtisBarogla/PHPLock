# Lockey Component

This library provides a simple way to attribute a lock to a specific user on a resource preserving its integrity.

0. [How to install](#0-installing-the-component)
1. [Why ?](#1-why)
2. [Make a component lockable](#2-make-a-component-lockable)
3. [How to use](#3-how-to-use)
4. [Normalizer](#4-normalizer)
5. [Adapter](#5-adapter)
6. [Token Pool](#6-token-pool)
7. [Locker](#7-locker)
8. [Contributing](#8-contributing)
9. [License](#9-license)

## 0. Installing the component

Lockey library can be installed via composer

~~~bash
$ composer require ness/lockey
~~~

**Requires** : 
- [ness/user](https://github.com/CurtisBarogla/User) library

~~~bash
$ composer require ness/user
~~~

## 1. Why ?

This library provides you a way to lock a resource at any stage of its usage (mostly when an editing process happen, can be extend when reading...) preserving its integrity in case of multiple access.

This library is fully **agnostic** therefore is not depending of a specific data storage system ; (example flock() for filesystem ; LOCK syntax MySql and so on...). It only requires you to declare an entity which implement a simple interface allowing you to set the resource representation interacting with the Locker.

This library allows you, via the interface, to set a hierarchy for your resource preserving informations integrity between your entities.

This library is also **fully unit tested**

## 2. Make a component lockable

Making a component lockable only requires you to make it compliant with the LockableResourceInterface.

Now let's see what each methods are for : 

- **getLockableName()** which will simply identify the resource. This identifier MUST be unique among all resources,
- **getLockableHierarchy()** which allows you to refer your resource to into a hierarchy.

A simple example (not very creative... sorry :( ) showing you how to set a lockable resource

~~~php
/**
 * A simple client representation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class BankClient implements LockableResourceInterface
{
    
    /**
     * Bank client id
     * 
     * @var string
     */
    public $clientId;
    
    /*
     * A set of methods/properties describing what a BankClient is 
     */
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableName()
     */
    public function getLockableName(): string
    {
        return "BankClient_{$this->clientId}";
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableHierarchy()
     */
    public function getLockableHierarchy(): ?array
    {
        return null; // no hierarchy
    }
    
}

/**
 * A simple bank account representation
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class BankAccount implements LockableResourceInterface
{
    
    /**
     * Client linked to this account
     *
     * @var BankClient
     */
    public $client;
    
    /**
     * Bank account id
     * 
     * @var string
     */
    public $accountId;
    
    /*
     * A set of methods/properties describing what a BankAccount is
     */
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableName()
     */
    public function getLockableName(): string
    {
        return "BankAccount_{$this->accountId}";
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableHierarchy()
     */
    public function getLockableHierarchy(): ?array
    {
        return [
            $this->client
        ]; 
        // a hierarchy to a bank client
        // when a bank account is in a lock state ; this client is locked too
        // therefore, the client of this account and this account, if locked, cannot be modified during the lock time
    }
    
}
~~~

## 3. How to use

Let's now how to use the component and what's better than a "concrete" use case ? (this assume the components described at section : [Make a component lockable](#2-make-a-component-lockable)).

This use case assumes the usage of the native implementation of LockerInterface and LockTokenPoolInterface.

~~~php
// initializing some users who has access to client accounts
$employedFoo = new User("Foo", null, ["ROLE_EMPLOYED"]);
$employedBar = new User("Bar", null, ["ROLE_EMPLOYED"]);

// the pool requires an adapter and a resource normalizer
$normalizer = new SHA1ResourceNormalizer();
$adapter = new ApcuLockTokenStoreAdapter(); // requires acpu
$pool = new LockTokenPool($adapter, $normalizer); // we have now a pool

// locker requires a LockTokenPoolInterface implementation
$locker = new Locker($pool); // now we have our locker configured, let's use it
~~~

Let's interact with the locker

~~~php
// assumes users, locker and resource initialized above

// let's exclusively lock the bank account of Mr. Moz (who's obviously rich... we select our clients carefully...).
$locker->exclusive($employedFoo, $bankAccountMrMoz, new DateInterval("PT20M"));
// done, we have now a lock acquired by "$employedFoo" for 20 minutes. This resource is now locked in read, write, edit context (expect for $employedFoo obviously)

// now let's free this resource
$locker->free($employedFoo, $bankAccountMrMoz, function(): void {
    // $this point to $bankAccountMrMoz
    updateBankAccount($bankAccountMrMoz); // assumes this is a MEGA function updating account informations
});
// in this case the user is able to write on the resource (no exception thrown) and the token associated to it has been removed and a new lock can be acquired

//let's now share a resource among multiple users
$locker->share($employedFoo, [$employedBar], $bankAccountMrMoz, new DateInterval("PT20M"));
// now the resource is shared - therefore accessible to $employedFoo AND $employedBar
// NOTE : in this configuration $employedBar CANNOT free the resource - resource for this user is only readable
$locker->free($employedBar, $bankAccountMrMoz, function(): void {
    // $this point to $bankAccountMrMoz
    updateBankAccount($this); // assumes this is a MEGA function updating account informations
});
// this will throw a LockTokenExpiredException
// if you wish to share write permission among all users, set the last parameter (share() method) to true
~~~

We've seen a simple use case briefly, for more information about the Locker component : see [here](#7-locker)

## 4. Normalizer

A normalizer is a simple interface allowing you to make a resource name valid for all kind a external storages.

It consists in a simple method **normalize()** which must convert your resource name respecting this simple rules : 
- > 3 <= 42 characters length,
- contains only [A-Za-z0-9] characters

### 4.1 SHA1ResourceNormalizer

This library comes with an implementation which simply apply a SHA1 on the resource name

~~~php
$normalizer = new SHA1ResourceNormalizer();

$normalizer->normalize("FooResource") // will return sha1 of FooResource
~~~

## 5. Adapter

An adapter is a simple component allowing to communicate with an external storage with raw values.

It consists in an interface : LockTokenStoreAdapterInterface containing 3 methods : 
- **get()** which is getting a raw value representing a lock token for the resource or null if no token,
- **add()** which takes as parameter a resource name, a raw value representing the lock token attached to this resource and the lock duration. This method returns a boolean informing you if the token has been stored with success,
- **delete()** which deletes a token by its resource name.

~~~php
$adapter = new LockTokenStoreAdapterImplementation();

// let's admit a previous token has been store for the resource FooResource
$adapter->get("FooResource"); // will return in this case the lock token setted for the resource FooResource
$adapter->get("BarResource"); // will in this case return null as no token has been setted for the resource BarResource

// let's add a token for the resource BarResource
$adapter->add("BarResource", "TokenForBarResourceRepresentation", 20); // store a token for BarResource for 20 seconds, returns true

// now let's remove this two
$adapter->remove("FooResource"); // will return true
$adapter->remove("BarResource"); // will return true

$adapter->remove("FooResource"); // will return false as no token for FooResource has been found
~~~

This library comes with 3 implementations of adapter.

### 5.1 ApcuLockTokenStoreAdapter

This implementation will simply use apcu as store. It requires so the acpu extension

~~~php
$adapter = new ApcuLockTokenStoreAdapter();

// your adapter is now using apcu
~~~

### 5.2 PSR-6 & PSR-16 LockTokenStoreAdapter

This implementations requires a PSR-6 or a PSR-16 Cache implementation to store your token

~~~php
$PSR6Cache = new PSRCacheItemPoolImplementation();
$PSR16Cache = new PSRSimpleCacheImplementation();

$adapterPSR6 = new CacheItemPoolLockTokenStoreAdapter($PSR6Cache);
$adapterPSR16 = new CacheLockTokenStoreAdapter($PSR16Cache);

// your adapter is using either PSR6 or PSR16
~~~

## 6. Token Pool

Token pool is the component allowing to handle the management of Lock token over resources.

All methods which interacts with an external storage are atomic.

The interface consists in 3 simple methods : 
- **getToken()** which try to find a token for the given resource ; it will return null if no token assigned,
- **saveToken()** which takes a source lock token to store and the resource associated,
- **removeToken()** which simply removes a lock token linked to the resource.

### 6.1 Getting a token

Let's see now how to get fetch a LockToken from the pool. This method will try to get a LockToken from the given resource and all resource declared into it hierarchy.

If none found, it will return null.

~~~php
// this exemple assumes a pool has been initialized in $pool

// let's assume the KekResource has no lock token assigned to it
$pool->getToken($kekResource); // will return null

// let's assume a LockToken has been setted into the main resource
$pool->getToken($fooResource); // this will return the lock token assigned to the FooResource

// let's assume the BarResource, not locked, has a declared hierarchy ["MozResource", "PozResource"] and MozResource is currently locked
$pool->getToken($barResource); // this will return a lock token assigned to the MozResource 
~~~

#### 6.2 Saving a LockToken

Take a look now on how to register a lock token.

This method requires a token which will be linked to the given resource and shared among all hierarchy (if declared).

This method can be considered atomic as if a resource declared a hierarchy and a lock token for a parent resource cannot be registered for whatever reason, no lock token will be stored at all and resource will stay is a free state.

~~~php
// this exemple assumes a pool has been initialized in $pool

// let's assume a simple $fooResource and a lock token ($token) as base token
$pool->saveToken($token, $fooResource); // returns true 
// that's it ; lock token is assigned to the resource $fooResource

// let's now assume a simple hierarchical resource
// MozResource -> BarResource -> FooResource
// MozResource is $mozResource
$pool->saveToken($mozToken, $mozResource); // returns true
// now, a token has been assigned to each of the resource
~~~

Let's see now how the pool handles errors for saving operation.

~~~php
// this exemple assumes a pool has been initialized in $pool

// let's assume a simple $fooResource and a lock token ($token) as base token
$pool->saveToken($token, $fooResource); // returns false for whatever reason, nothing is stored at all as it's a simple resource

// let's now assume a simple hierarchical resource
// MozResource -> BarResource -> FooResource
// MozResource is $mozResource
$result = $pool->saveToken($mozToken, $mozResource); // imagine FooResource token failed to be stored - $result === false
~~~

If false is returned by the saving process, the pool considers its state restored to a previous one.

Taking this example : 
- lock token assigned to MozResource has been stored successfully,
- lock token assigned to BarResource has been stored successfully,
- an error happen when storing the one assigned to FooResource.

Each tokens successfully stored MUST **never** be stored as the operation failed at a specific point.

However, a TokenPoolTransactionErrorException might be thrown if the LokenTokenPool fails to restore its previous state ; so human decision should be done in this case (but should never happen... right ?).

#### 6.3 Remove a LockToken

And finally, removing a lock token from the pool.

Like the saving process, this operation can be considered atomic.

~~~php
// this exemple assumes a pool has been initialized in $pool

// let's assume a simple FooResource -> $fooResource

// a token is stored for the resource
$pool->removeToken($fooResource); // will return true : token removed with success

// $fooResource has now a hierarchy
// MozResource -> BarResource -> FooResource
$pool->removeToken($fooResource); // will return true and all lock tokens has been removed
~~~

Let's see now how the pool handles errors for saving operation.

~~~php
// this exemple assumes a pool has been initialized in $pool

// let's assume a simple $fooResource
$pool->removeToken($fooResource); // returns false for whatever reason (no token found or the token cannot be removed...)

// let's now assume a simple hierarchical resource
// MozResource -> BarResource -> FooResource
// MozResource is $mozResource
$result = $pool->remove($mozResource); // imagine FooResource token failed to be removed
~~~

If false is returned by the removing process, the pool considers its state restored to a previous one.

Taking this example : 
- lock token assigned to MozResource has been removed successfully,
- lock token assigned to BarResource has been removed successfully,
- an error happen when revoving the one assigned to FooResource.

Each tokens successfully removed MUST be **restored** to its original value as the operation failed at a specific point.

However, a TokenPoolTransactionErrorException might be thrown if the LokenTokenPool fails to restore its previous state ; so human decision should be done in this case (but should never happen... right ?).

#### 6.4 Implementation

Let's see how to initialize the implementation of the pool furnished by this library

This implemenation is based on an adapter which will communicate with an external storage and a normalizer responsible to convert a resource name making it valid for all storages.

~~~php
$adapter = LockTokenStoreAdapterImplementation();
$normalizer = new ResourceNormalizerImplementation();

$pool = new LockTokenPool($adapter, $normalizer);

// that'it, you have your pool initialized
~~~

## 7. Locker

Locker is the main component allowing to set locks (exclusive, share and bypass) and free a resource.

It allows you too to get informations about the current locking state of a resource.

Locker provides you methods : 
- **getState()** fetching information about a lock applied to the given resource over the user,
- **exclusive()** applying an exclusive lock (strict access to the resource) on the resource for the given user for a certain amount of time,
- **share()** acting as an exclusive lock but allowing you to share the lock token among multiple users,
- **free()** which will simply free the resource from the current lock,
- **bypass()** which revokes all lock tokens applied to the given resource followed by and exclusive lock on the resource for the given user.

### 7.1 Lock Token

Before anything let's talk about the lock token.

Lock token is a simple class providing you informations about a lock applied to a resource. 

A lock token SHOULD never be instantiated by the user - let the locker the responsability to initialize it for you.

The lock token has two states : mutable and immutable. The token is mutable only during its initialization (therefore never when you're dealing with) and immutable when fetched (majority of the time you're interacting with).

Let's see the methods you should only consider : 
- **getResource()** : access to the resource name which the token is about,
- **getValidity()** : a DateTime (immutable) representing when the resource will be freed,
- **getMaster()** : the user id which the token is linked.

Trying to alter the token in an immutable state will result a LogicException.

### 7.2 Acquiring a LockState

A lock state (as named) give the **current** locking state applied to a resource implying the given user.

It consists in some simple methods : 
- **getResource()** which will return the resource instance concerned by the state,
- **isLocked()** checks if a lock has been applied to the resource
- **isAccessible()** checks if the user setted is allowed to "read" the resource. Will obviously returns true if no lock has been applied,
- **getToken()** returns the token applied to the resource if one is found. Returns null if not locked.

Simple case when a resource has been not previously locked

~~~php
// no lock has been applied on the resource yet
$state = $locker->getState($fooResource, $fooUser);
$state->getResource(); // a reference to $fooResource
$state->isLocked(); // returns false as the resource is not locked
$state->isAccessible(); // true
$state->getToken(); // null - no lock token found
~~~

Now an example when an exclusive lock has been applied

~~~php
// this exemple assumes a locker has been initialized in $locker

// let's assume a $fooResource, a $fooUser and a $barUser 

// an exclusive lock accorded to $fooUser has been applied
$state = $locker->getState($fooResource, $fooUser);
$state->isLocked(); // will return true
$state->isAccessible(); // will return true

// now for $barUser ; same conditions
$state = $locker->getState($fooResource, $barUser);
$state->isLocked(); // will return true
$state->isAccessible(); // will return false

$state->getToken(); // in both cases, will return an immutable lock token corresponding the current lock
~~~

And finally, when a share lock has been applied

~~~php
// this exemple assumes a locker has been initialized in $locker

// let's assume a $fooResource, a $fooUser, a $barUser and a $mozUser 

// a share lock accorded to $fooUser and $barUser has been applied
$state = $locker->getState($fooResource, $fooUser);
$state->isLocked(); // will return true
$state->isAccessible(); // will return true

// now for $barUser ; same conditions
$state = $locker->getState($fooResource, $barUser);
$state->isLocked(); // will return true
$state->isAccessible(); // will return true

// now for $mozUser ; same conditions
$state = $locker->getState($fooResource, $barUser);
$state->isLocked(); // will return true
$state->isAccessible(); // will return false

$state->getToken(); // in all cases, will return an immutable lock token corresponding the current lock
~~~

**! Important !**

Lock tokens fetched by **getToken()** method are immutables. Therefore, trying to update their properties will result a LogicException.

### 7.3 Locking resources

Let's see now how to lock a resource. As already stated, it is possible to lock a resource in 3 different ways.

**! Note !**

Trying to lock an already locked resource (except for bypass) will not overwrite the current locking state. Basically, nothing happen.

#### 7.3.1 Exclusive lock

Exclusive lock is the most simple lock.

It lock in read and write context the resource, therefore, only the user who has initiate the lock will be able to access the resource for the given amount of time.

~~~php
// this exemple assumes a locker has been initialized in $locker

// let's assume a $fooResource, a $fooUser
$locker->exclusive($fooUser, $fooResource, new DateInterval("PT20M"));
// that's it, the resource is locked for 20 minutes - only accessible for $fooUser
~~~

**! Important !**

If the lock cannot be acquired (for the resource and all ones declared into it's potential hierarchy) for whatever reason, a LockErrorException will be thrown. 

#### 7.3.2 Share lock

Share lock is a little bit more permissive and allows you to share the resource with a list of users.

By default, the resource is shared only in read context, by can be extend the write context simply by setting the last argument to true.

~~~php
// this exemple assumes a locker has been initialized in $locker

// let's assume a $fooResource, a $fooUser, a $barUser
$locker->share($fooUser, [$barUser], $fooResource, new DateInterval("PT20M"));
// that's it, the resource is locked for 20 minutes - only accessible for $fooUser (read/write) and $barUser (read only)

// let's fully share the resource now
$locker->share($fooUser, [$barUser], $fooResource, new DateInterval("PT20M"), true);
// now fully shared among all users declared in all contexts
~~~

**! Important !**

If the lock cannot be acquired (for the resource and all ones declared into it's potential hierarchy) for whatever reason, a LockErrorException will be thrown.

#### 7.3.3 Bypass lock

Now the most "tricky" one. It will revoke the lock accorded to a resource and set a new one, exclusively only, for the given user. This should be used exceptionnally has work done on the resource by other user will be loss.

~~~php
// this exemple assumes a locker has been initialized in $locker

// let's assume a $fooResource, a $fooUser
$locker->bypass($fooUser, $fooResource, new DateInterval("PT20M"));
// that's it, the previous lock (if exists) has been revoked and a new one has been granted to $fooUser for 20 minutes
~~~

**! Important !**

If the lock cannot be acquired (for the resource and all ones declared into it's potential hierarchy) for whatever reason, a LockErrorException will be thrown and the previous lock token is still preserved.

### 7.4 Free resources

Let's see now how to free a resource and apply modification to it.

**! Note !**

Trying to free an non-locked resource and modify it will result a LockTokenExpiredException as nothing MUST be performed if not previously attributed to a user.

This method acts as a simple wrapper interfacing your action on the resource and the locker which will determined the validity of the lock token before applying the modification.

~~~php
// this exemple assumes a locker has been initialized in $locker

// let's assume a $fooResource, a $fooUser and a lock previously applied to the user
$locker->free($fooUser, $fooResource, function() use ($fooResourceManager): void {
    // in this context $this refers to $fooResource
    $fooResourceManager->update($this);
});
~~~

If the lock has been revoked for the given resource and user, a LockTokenExpiredException will be thrown.

**! Important !**

If the resource cannot be freed for whatever reason, the lock will be restored to its original value and the action will not be performed on the resource and a UnlockErrorException will be thrown.

**! Important !**

If an error happen during the modification action, no verification are done on the result of it. The resource will be freed no matter what.

~~~php
// this exemple assumes a locker has been initialized in $locker

// let's assume a $fooResource, a $fooUser and a lock previously applied to the user
$locker->free($fooUser, $fooResource, function() use ($fooResourceManager): void {
    $fooResourceManager->update($this); // assume an exception happen here, the resource will be freed
});
~~~

### 7.5 Implementation

This library comes with an implementation of LockerInterface.

It only required you to set a LockTokenPoolInterface implementation.

~~~php
$lockTokenPool = new LockTokenPoolImplementation();
$locker = new Locker($lockTokenPool);

// that's it, your locker is initialized
~~~

## 8. Contributing

Found something **wrong** (nothing is perfect) ? Wanna talk or participate ? <br />
Issue the case or contact me at [curtis_barogla@outlook.fr](mailto:curtis_barogla@outlook.fr)

## 9. License

The Ness Lockey component is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
