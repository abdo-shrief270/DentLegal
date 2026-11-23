<?php

namespace Tests\Unit;

use App\Modules\Cases\Enums\CaseStatus;
use PHPUnit\Framework\TestCase;

class CaseStatusTest extends TestCase
{
    public function test_happy_path_is_allowed(): void
    {
        $path = [CaseStatus::Draft, CaseStatus::Analyzing, CaseStatus::AiReady, CaseStatus::LawyerReview, CaseStatus::Approved];
        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertTrue($path[$i]->canTransitionTo($path[$i + 1]), $path[$i]->value.' → '.$path[$i + 1]->value);
        }
    }

    public function test_failed_can_be_retried_and_approved_is_final(): void
    {
        $this->assertTrue(CaseStatus::Analyzing->canTransitionTo(CaseStatus::Failed));
        $this->assertTrue(CaseStatus::Failed->canTransitionTo(CaseStatus::Analyzing));
        $this->assertSame([], CaseStatus::Approved->allowedNext());
    }

    public function test_skipping_steps_is_not_allowed(): void
    {
        $this->assertFalse(CaseStatus::Draft->canTransitionTo(CaseStatus::LawyerReview));
        $this->assertFalse(CaseStatus::AiReady->canTransitionTo(CaseStatus::Approved));
        $this->assertFalse(CaseStatus::LawyerReview->canTransitionTo(CaseStatus::Analyzing));
    }
}
