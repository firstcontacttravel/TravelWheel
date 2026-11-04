<?php

namespace App\Services\Corporate;

use DomainException;

/** Posting this entry would take the wallet below what its company may spend. */
class InsufficientWalletBalance extends DomainException {}
