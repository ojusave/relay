<?php

namespace App\Service\Ip;

use App\Entity\IpAddress;
use App\Entity\Queue;
use App\Entity\Type\WarmupStatus;
use App\Entity\WarmupSchedule;
use Doctrine\ORM\EntityManagerInterface;

readonly class IpSelector
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function selectForQueue(Queue $queue, int $recipientCount = 1): ?IpAddress
    {
        /** @var IpAddress[] $ips */
        $ips = $this->em->createQuery('
                SELECT ip, ws
                FROM App\Entity\IpAddress ip
                LEFT JOIN ip.warmupSchedules ws WITH ws.status = :warmingStatus
                WHERE ip.queue = :queue
            ')
            ->setParameter('queue', $queue)
            ->setParameter('warmingStatus', WarmupStatus::WARMING)
            ->getResult();

        if (empty($ips)) {
            return null;
        }

        shuffle($ips);

        $conn = $this->em->getConnection();

        foreach ($ips as $ip) {
            $warmupSchedules = $ip->getWarmupSchedules();
            $warmup = $warmupSchedules->isEmpty() ? null : $warmupSchedules->first();

            if ($warmup instanceof WarmupSchedule && $warmup->getStatus() === WarmupStatus::WARMING) {
                if ($warmup->getSentToday() + $recipientCount <= $warmup->getMaxToday()) {
                    $conn->executeStatement(
                        'UPDATE warmup_schedules SET sent_today = sent_today + :count WHERE id = :id',
                        [
                            'count' => $recipientCount,
                            'id' => $warmup->getId(),
                        ]
                    );
                    return $ip;
                }
			} else {
				return $ip;
			}
        }

        return null;
    }
}
