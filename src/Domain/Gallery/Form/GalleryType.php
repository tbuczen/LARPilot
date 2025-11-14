<?php

namespace App\Domain\Gallery\Form;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\Gallery\Entity\Enum\GalleryVisibility;
use App\Domain\Gallery\Entity\Gallery;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Url;

class GalleryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'gallery.title',
                'required' => true,
                'attr' => ['placeholder' => 'gallery.title_placeholder'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'gallery.description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'gallery.description_placeholder',
                    'rows' => 5,
                ],
            ])
            ->add('photographer', EntityType::class, [
                'label' => 'gallery.photographer',
                'class' => LarpParticipant::class,
                'choice_label' => fn (LarpParticipant $participant): string =>
                    $participant->getUser()->getUsername(),
                'required' => true,
                'placeholder' => 'choose',
                'autocomplete' => true,
                'query_builder' => function (LarpParticipantRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('p')
                        ->join('p.user', 'u')
                        ->where('p.larp = :larp')
                        ->setParameter('larp', $larp)
                        ->orderBy('u.username', 'ASC');
                },
            ])
            ->add('externalAlbumUrl', UrlType::class, [
                'label' => 'gallery.external_album_url',
                'required' => false,
                'attr' => ['placeholder' => 'https://facebook.com/...'],
                'constraints' => [new Url()],
                'help' => 'gallery.external_album_url_help',
            ])
            ->add('zipDownloadUrl', UrlType::class, [
                'label' => 'gallery.zip_download_url',
                'required' => false,
                'attr' => ['placeholder' => 'https://drive.google.com/...'],
                'constraints' => [new Url()],
                'help' => 'gallery.zip_download_url_help',
            ])
            ->add('zipFileUpload', FileType::class, [
                'label' => 'gallery.zip_file_upload',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '500M',
                        'mimeTypes' => ['application/zip', 'application/x-zip-compressed'],
                        'mimeTypesMessage' => 'gallery.invalid_zip_file',
                    ]),
                ],
                'help' => 'gallery.zip_file_upload_help',
            ])
            ->add('visibility', EnumType::class, [
                'label' => 'gallery.visibility',
                'class' => GalleryVisibility::class,
                'choice_label' => fn (GalleryVisibility $visibility): string => $visibility->getLabel(),
                'required' => true,
                'expanded' => false,
                'help' => 'gallery.visibility_help',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'submit',
                'priority' => -10000,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gallery::class,
            'larp' => null,
            'translation_domain' => 'forms',
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
