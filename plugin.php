<?php

namespace Plugins\Sirsoft\Ckeditor5;

use App\Extension\AbstractPlugin;

/**
 * CKEditor 5 WYSIWYG 에디터 플러그인
 *
 * extension_point: "html_editor" 슬롯을 통해 기존 HtmlEditor를 교체합니다.
 * 미설치 시 기존 HtmlEditor로 자동 폴백됩니다.
 */
class Plugin extends AbstractPlugin
{
    /**
     * 플러그인 설정 스키마 반환
     *
     * @return array 설정 스키마
     */
    public function getSettingsSchema(): array
    {
        return [
            'imageUpload' => [
                'type' => 'boolean',
                'default' => true,
                'label' => [
                    'ko' => '이미지 업로드',
                    'en' => 'Image Upload',
                ],
                'hint' => [
                    'ko' => '에디터에서 이미지 업로드 기능을 활성화합니다.',
                    'en' => 'Enable image upload functionality in the editor.',
                ],
                'required' => false,
            ],
            'imageMaxSizeMb' => [
                'type' => 'integer',
                'default' => 2,
                'label' => [
                    'ko' => '이미지 최대 크기 (MB)',
                    'en' => 'Image Max Size (MB)',
                ],
                'hint' => [
                    'ko' => '업로드 가능한 이미지의 최대 파일 크기입니다.',
                    'en' => 'Maximum file size for uploadable images.',
                ],
                'required' => false,
            ],
            'editorHeight' => [
                'type' => 'integer',
                'default' => 400,
                'label' => [
                    'ko' => '에디터 높이 (px)',
                    'en' => 'Editor Height (px)',
                ],
                'hint' => [
                    'ko' => '에디터 영역의 최소 높이입니다.',
                    'en' => 'Minimum height of the editor area.',
                ],
                'required' => false,
            ],
            'toolbar' => [
                'type' => 'enum',
                'options' => ['standard', 'minimal', 'full'],
                'default' => 'standard',
                'label' => [
                    'ko' => '툴바 유형',
                    'en' => 'Toolbar Type',
                ],
                'hint' => [
                    'ko' => '에디터 툴바 구성을 선택합니다.',
                    'en' => 'Select the editor toolbar configuration.',
                ],
                'required' => false,
            ],
            // 선택지가 코어 카탈로그 + 플러그인 훅 등록 디스크로 동적이라 enum 불가 — string.
            // 존재하지 않는 디스크 값은 resolvePublicAssetDisk() 가 스트리밍으로 안전 폴백한다.
            'public_asset_disk' => [
                'type' => 'string',
                'max' => 100,
                'default' => '',
                'label' => [
                    'ko' => '공개 자산 디스크',
                    'en' => 'Public Asset Disk',
                ],
                'hint' => [
                    'ko' => '이 플러그인만 다른 디스크를 쓰려면 선택합니다. 비우면 코어 공개 자산 디스크 설정을 따릅니다.',
                    'en' => 'Select to use a different disk for this plugin only. Leave empty to follow the core public asset disk setting.',
                ],
                'required' => false,
            ],
        ];
    }

    /**
     * 완전 공개 자산 스토리지 카테고리 목록
     *
     * 이 카테고리들만 공개 자산 디스크 설정을 따른다. 권한 검사가 걸린 자산은
     * 직접 URL 이 권한을 우회하므로 포함하지 않는다. 에디터가 이미지 외의
     * 공개 자산 업로드를 지원하게 되면 여기에만 카테고리를 추가하면 된다.
     *
     * @var list<string>
     */
    private const PUBLIC_ASSET_CATEGORIES = ['images'];

    /**
     * 카테고리별 스토리지 디스크 이름 반환
     *
     * 완전 공개 자산 카테고리만 공개 자산 디스크(플러그인 설정 public_asset_disk >
     * 코어 전역 core.storage.public_asset_disk)를 따르고, 설정 등 나머지 카테고리는
     * 기본 디스크를 유지합니다. 미설정/고아 디스크는 기본 디스크로 폴백해 기존
     * 스트리밍 동작을 보존합니다.
     *
     * 플러그인 설정 조회는 공개 자산 카테고리에서만 수행합니다 — 'settings' 카테고리에서
     * 조회하면 설정 로드와 재귀 고리가 생깁니다 (AbstractPlugin 주석 참조).
     *
     * @param  string  $category  카테고리
     * @return string 디스크 이름
     */
    public function getStorageDiskFor(string $category): string
    {
        if (! in_array($category, self::PUBLIC_ASSET_CATEGORIES, true)) {
            return $this->getStorageDisk();
        }

        $override = plugin_setting('sirsoft-ckeditor5', 'public_asset_disk', '');

        return $this->resolvePublicAssetDisk(is_string($override) ? $override : '')
            ?? $this->getStorageDisk();
    }

    /**
     * 플러그인이 관리하는 동적 테이블 목록 반환
     *
     * @return array 테이블명 배열
     */
    public function getDynamicTables(): array
    {
        return [
            'ckeditor5_image_uploads',
        ];
    }
}
