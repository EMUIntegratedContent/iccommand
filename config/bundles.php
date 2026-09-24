<?php

return [
		Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
		Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true], // Move this up
		Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
		Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
		Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
		Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
		Nelmio\CorsBundle\NelmioCorsBundle::class => ['all' => true],
		Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle::class => ['all' => true],
		Liip\ImagineBundle\LiipImagineBundle::class => ['all' => true],
		Symfony\WebpackEncoreBundle\WebpackEncoreBundle::class => ['all' => true],
		// Dev/Test bundles at the very end
		Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
		Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true, 'test' => true],
		Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
];
