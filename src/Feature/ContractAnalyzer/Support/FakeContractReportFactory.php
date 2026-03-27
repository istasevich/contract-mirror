<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Support;

use App\Feature\ContractAnalyzer\DTO\ContractAnalysisReportViewDto;
use App\Feature\ContractAnalyzer\DTO\ContractIssueViewDto;
use App\Feature\ContractAnalyzer\DTO\MissingProtectionViewDto;

final class FakeContractReportFactory
{
    public function make(): ContractAnalysisReportViewDto
    {
        return new ContractAnalysisReportViewDto(
            documentName: 'Freelance Service Agreement.pdf',
            documentType: 'PDF',
            language: 'English',
            riskScore: 74,
            overallRisk: 'HIGH',
            executiveSummary: 'This contract contains several freelancer-unfriendly terms, especially around revisions, termination, liability, and IP transfer. The payment language is vague, and the client has too much discretion over acceptance. It should not be signed as-is without revisions.',
            finalRecommendation: 'Sign only after edits',
            signingRecommendation: 'Push back on unlimited revisions, require clear payment deadlines, limit liability, and make IP transfer conditional on full payment.',
            issues: [
                new ContractIssueViewDto(
                    title: 'Unlimited revisions',
                    severity: 'HIGH',
                    category: 'Scope / Revisions',
                    originalClause: 'Contractor agrees to revise the work until the Client is satisfied.',
                    plainExplanation: 'The client can keep asking for changes with no clear limit.',
                    whyItMatters: 'This can create endless extra work without extra pay.',
                    suggestedRewrite: 'The agreed fee includes up to 2 revision rounds. Additional revisions are billed separately at the contractor’s standard hourly rate.'
                ),
                new ContractIssueViewDto(
                    title: 'Vague payment deadline',
                    severity: 'MEDIUM',
                    category: 'Payment',
                    originalClause: 'Client agrees to pay Contractor within a reasonable time after delivery.',
                    plainExplanation: 'The contract does not define when payment is actually due.',
                    whyItMatters: 'A vague deadline makes delayed payment harder to challenge.',
                    suggestedRewrite: 'Client shall pay all undisputed invoices within 7 calendar days of receipt.'
                ),
                new ContractIssueViewDto(
                    title: 'Immediate IP transfer',
                    severity: 'HIGH',
                    category: 'Intellectual Property',
                    originalClause: 'All work produced shall become the sole property of the Client immediately upon creation.',
                    plainExplanation: 'Ownership transfers before the contractor is paid.',
                    whyItMatters: 'The client may get the work product even if payment is delayed or never made.',
                    suggestedRewrite: 'Ownership transfers to Client only after full payment of all amounts due under this Agreement.'
                ),
                new ContractIssueViewDto(
                    title: 'One-sided termination',
                    severity: 'HIGH',
                    category: 'Termination',
                    originalClause: 'Client may terminate this agreement at any time without prior notice.',
                    plainExplanation: 'The client can walk away immediately with no protection for the contractor.',
                    whyItMatters: 'You may lose expected revenue and time already invested.',
                    suggestedRewrite: 'Either party may terminate on 14 days’ written notice. Client remains responsible for payment for all work performed up to the termination date.'
                ),
            ],
            missingProtections: [
                new MissingProtectionViewDto(
                    title: 'No late payment protection',
                    category: 'Payment',
                    whyItMatters: 'There is no consequence for delayed payment, which weakens enforcement.',
                    suggestedClause: 'Late payments accrue interest at 1.5% per month or the maximum amount permitted by law, whichever is lower.'
                ),
                new MissingProtectionViewDto(
                    title: 'No liability cap',
                    category: 'Liability',
                    whyItMatters: 'Without a cap, the contractor may face disproportionate financial exposure.',
                    suggestedClause: 'Contractor’s total liability under this Agreement shall not exceed the total fees paid under this Agreement.'
                ),
            ],
        );
    }
}
