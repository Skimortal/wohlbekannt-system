<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/password')]
class PasswordResetController extends ApiController
{
    private const TOKEN_TTL = '+1 hour';
    private const MIN_LENGTH = 8;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $repo,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly MailerInterface $mailer,
        private readonly string $frontendUrl,
        private readonly string $mailerFrom,
    ) {
    }

    #[Route('/forgot', methods: ['POST'])]
    public function forgot(Request $request): JsonResponse
    {
        $email = trim((string) ($this->body($request)['email'] ?? ''));
        $user = '' !== $email ? $this->repo->findOneBy(['email' => $email]) : null;

        $resetUrl = null;
        if ($user instanceof User && $user->isActive()) {
            $token = bin2hex(random_bytes(32));
            $user->setResetTokenHash(hash('sha256', $token));
            $user->setResetTokenExpiresAt(new \DateTimeImmutable(self::TOKEN_TTL));
            $this->em->flush();

            $resetUrl = rtrim($this->frontendUrl, '/').'/passwort-zuruecksetzen?token='.$token;
            $this->sendMail($user, $resetUrl);
        }

        // Always generic — never reveal whether an address exists.
        $response = ['message' => 'Falls ein Konto mit dieser E-Mail existiert, wurde ein Link zum Zurücksetzen gesendet.'];
        // Dev convenience: expose the link so the flow is testable without SMTP.
        if ($resetUrl && 'dev' === $this->getParameter('kernel.environment')) {
            $response['devResetUrl'] = $resetUrl;
        }

        return $this->json($response);
    }

    #[Route('/reset', methods: ['POST'])]
    public function reset(Request $request): JsonResponse
    {
        $data = $this->body($request);
        $token = (string) ($data['token'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if (strlen($password) < self::MIN_LENGTH) {
            return $this->json(['error' => sprintf('Das Passwort muss mindestens %d Zeichen lang sein.', self::MIN_LENGTH)], 422);
        }
        if ('' === $token) {
            return $this->json(['error' => 'Ungültiger Link.'], 400);
        }

        $user = $this->repo->findOneBy(['resetTokenHash' => hash('sha256', $token)]);
        if (!$user instanceof User
            || null === $user->getResetTokenExpiresAt()
            || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['error' => 'Der Link ist ungültig oder abgelaufen.'], 400);
        }

        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setResetTokenHash(null);
        $user->setResetTokenExpiresAt(null);
        $this->em->flush();

        return $this->json(['message' => 'Passwort wurde geändert. Sie können sich jetzt anmelden.']);
    }

    private function sendMail(User $user, string $resetUrl): void
    {
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Passwort zurücksetzen – wohlbekannt')
            ->text(
                "Hallo {$user->getName()},\n\n".
                "Sie haben das Zurücksetzen Ihres Passworts angefordert. Über folgenden Link können Sie ein neues Passwort vergeben (gültig für 1 Stunde):\n\n".
                "{$resetUrl}\n\n".
                "Falls Sie das nicht waren, können Sie diese E-Mail ignorieren.\n\n".
                'wohlbekannt'
            );

        try {
            $this->mailer->send($email);
        } catch (\Throwable) {
            // Don't leak mailer errors to the caller; the reset token is already stored.
        }
    }
}
