<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models;

use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Builders\Processors\App\Models\UserModelProcessor;

/**
 * Class UserModelBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Models
 */
class UserModelBuilder extends ModelBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        UserModelProcessor::class,
    ];
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'User',
        'extend' => 'Authenticatable',
    ];
    /**
     * @var string|null
     */
    protected string $filename = 'User.php';
    /**
     * @var bool
     */
    private bool $shouldUseNotifiable = false;
    /**
     * @var bool
     */
    private bool $shouldUseMustVerifyEmail = false;
    /**
     * @var string
     */
    private string $authenticatable = User::class;

    /**
     * @return ModelBuilder
     */
    public function prepare(): ModelBuilder
    {
        // Instantiates the property builders etc.
        parent::prepare();

        // Build user model specific use statements and defaults.
        return $this
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return UserModelBuilder
     */
    protected function buildUseStatements(): UserModelBuilder
    {
        $this
            ->useNotifiable()
            ->useMustVerifyEmail()
            ->useAuthenticatable();

        if ($this->shouldSoftDelete()) {
            $this->use(SoftDeletes::class);
        }

        return $this;
    }

    /**
     * @return UserModelBuilder
     */
    private function setDefaults(): UserModelBuilder
    {
        return $this
            ->setProcessFilterables(false)
            ->setBuildPresenter(false);
    }

    /**
     * @return $this
     */
    public function useAuthenticatable(): UserModelBuilder
    {
        $this->use($this->getAuthenticatable(), 'Authenticatable');

        return $this;
    }

    /**
     * @return UserModelBuilder
     */
    public function useMustVerifyEmail(): UserModelBuilder
    {
        if ($this->shouldUseMustVerifyEmail()) {
            $this->use(MustVerifyEmail::class);
        }

        return $this;
    }

    /**
     * @return UserModelBuilder
     */
    public function useNotifiable(): UserModelBuilder
    {
        if ($this->shouldUseNotifiable()) {
            $this->use(Notifiable::class);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthenticatable(): string
    {
        return $this->authenticatable;
    }

    /**
     * @param string $authenticatable
     * @return UserModelBuilder
     */
    public function setAuthenticatable(string $authenticatable): UserModelBuilder
    {
        $this->authenticatable = $authenticatable;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldUseMustVerifyEmail(): bool
    {
        return $this->shouldUseMustVerifyEmail;
    }

    /**
     * @return bool
     */
    public function shouldUseNotifiable(): bool
    {
        return $this->shouldUseNotifiable;
    }

    /**
     * @param bool $shouldUseNotifiable
     * @return UserModelBuilder
     */
    public function setShouldUseNotifiable(bool $shouldUseNotifiable): UserModelBuilder
    {
        $this->shouldUseNotifiable = $shouldUseNotifiable;
        return $this;
    }

    /**
     * @param bool $shouldUseMustVerifyEmail
     * @return UserModelBuilder
     */
    public function setShouldUseMustVerifyEmail(bool $shouldUseMustVerifyEmail): UserModelBuilder
    {
        $this->shouldUseMustVerifyEmail = $shouldUseMustVerifyEmail;

        return $this;
    }
}
