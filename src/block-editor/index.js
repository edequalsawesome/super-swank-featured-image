import { __ } from '@wordpress/i18n';
import { MediaUpload } from '@wordpress/block-editor';
import { Button, PanelBody, SelectControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

const CropPositionControl = ({ platform, value = { x: 'center', y: 'center' }, onChange }) => {
    const positions = {
        x: [
            { label: __('Left', 'super-swank-featured-image'), value: 'left' },
            { label: __('Center', 'super-swank-featured-image'), value: 'center' },
            { label: __('Right', 'super-swank-featured-image'), value: 'right' }
        ],
        y: [
            { label: __('Top', 'super-swank-featured-image'), value: 'top' },
            { label: __('Center', 'super-swank-featured-image'), value: 'center' },
            { label: __('Bottom', 'super-swank-featured-image'), value: 'bottom' }
        ]
    };

    return (
        <div className="ssfi-crop-position-control">
            <h4>{platform}</h4>
            <div style={{ display: 'flex', gap: '10px' }}>
                <SelectControl
                    label={__('Horizontal Position', 'super-swank-featured-image')}
                    value={value?.x || 'center'}
                    options={positions.x}
                    onChange={(x) => onChange({ ...value, x })}
                />
                <SelectControl
                    label={__('Vertical Position', 'super-swank-featured-image')}
                    value={value?.y || 'center'}
                    options={positions.y}
                    onChange={(y) => onChange({ ...value, y })}
                />
            </div>
        </div>
    );
};

const SuperSwankFeaturedImagePanel = () => {
    const defaultCropPositions = {
        facebook: { x: 'center', y: 'center' },
        twitter: { x: 'center', y: 'center' },
        instagram: { x: 'center', y: 'center' },
        pinterest: { x: 'center', y: 'center' }
    };

    const [defaultImage, setDefaultImage] = useState(0);
    const [imageUrl, setImageUrl] = useState('');
    const [cropPositions, setCropPositions] = useState(defaultCropPositions);
    const [isSaving, setIsSaving] = useState(false);

    // Get the current settings from WordPress
    const settings = useSelect(select => {
        const siteSettings = select('core').getEditedEntityRecord('root', 'site');
        return {
            defaultImage: siteSettings?.ssfi_default_image || 0,
            cropPositions: siteSettings?.ssfi_crop_positions || defaultCropPositions
        };
    }, []);

    // Update state when settings change
    useEffect(() => {
        if (settings.defaultImage !== defaultImage) {
            setDefaultImage(settings.defaultImage);
            if (settings.defaultImage) {
                wp.media.attachment(settings.defaultImage).fetch().then(() => {
                    setImageUrl(wp.media.attachment(settings.defaultImage).get('url'));
                });
            } else {
                setImageUrl('');
            }
        }

        // Ensure all platforms have valid crop positions
        const updatedCropPositions = Object.keys(defaultCropPositions).reduce((acc, platform) => {
            acc[platform] = {
                x: settings.cropPositions[platform]?.x || 'center',
                y: settings.cropPositions[platform]?.y || 'center'
            };
            return acc;
        }, {});

        setCropPositions(updatedCropPositions);
    }, [settings]);

    const { editEntityRecord, saveEditedEntityRecord } = useDispatch('core');

    const updateSettings = async (updates) => {
        setIsSaving(true);
        try {
            // Update the entity record
            await editEntityRecord('root', 'site', undefined, updates);
            // Save the changes
            await saveEditedEntityRecord('root', 'site');

            // If we're updating crop positions, trigger the regeneration
            if (updates.ssfi_crop_positions) {
                await apiFetch({
                    path: addQueryArgs('/wp/v2/ssfi/regenerate-crops', {
                        _wpnonce: window.ssfiSettings.restNonce
                    }),
                    method: 'POST'
                });
            }
        } catch (error) {
            console.error('Failed to save settings:', error);
        } finally {
            setIsSaving(false);
        }
    };

    const onSelectImage = (media) => {
        setDefaultImage(media.id);
        setImageUrl(media.url);
        updateSettings({
            ssfi_default_image: media.id
        });
    };

    const removeImage = () => {
        setDefaultImage(0);
        setImageUrl('');
        updateSettings({
            ssfi_default_image: 0
        });
    };

    const updateCropPosition = (platform, position) => {
        const newPositions = {
            ...cropPositions,
            [platform]: position
        };
        setCropPositions(newPositions);
        updateSettings({
            ssfi_crop_positions: newPositions
        });
    };

    const platforms = {
        facebook: __('Facebook/LinkedIn', 'super-swank-featured-image'),
        twitter: __('Twitter', 'super-swank-featured-image'),
        instagram: __('Instagram', 'super-swank-featured-image'),
        pinterest: __('Pinterest', 'super-swank-featured-image')
    };

    return (
        <PanelBody
            title={__('Default Featured Image', 'super-swank-featured-image')}
            initialOpen={true}
        >
            <div className="ssfi-settings-panel">
                <div className="ssfi-image-preview">
                    {imageUrl && (
                        <img
                            src={imageUrl}
                            alt=""
                            style={{
                                maxWidth: '100%',
                                marginBottom: '1em',
                                display: 'block'
                            }}
                        />
                    )}
                </div>
                <MediaUpload
                    onSelect={onSelectImage}
                    allowedTypes={['image']}
                    value={defaultImage}
                    render={({ open }) => (
                        <div>
                            <Button
                                onClick={open}
                                variant="secondary"
                                className="editor-post-featured-image__toggle"
                                style={{ 
                                    width: '100%', 
                                    marginBottom: defaultImage ? '0.5em' : 0,
                                    display: 'flex',
                                    justifyContent: 'center',
                                    alignItems: 'center',
                                    minHeight: '40px'
                                }}
                                disabled={isSaving}
                            >
                                {defaultImage
                                    ? __('Replace Image', 'super-swank-featured-image')
                                    : __('Select Default Image', 'super-swank-featured-image')}
                            </Button>
                            {defaultImage > 0 && (
                                <Button
                                    onClick={removeImage}
                                    variant="secondary"
                                    isDestructive
                                    style={{ 
                                        width: '100%', 
                                        marginBottom: '1em',
                                        display: 'flex',
                                        justifyContent: 'center',
                                        alignItems: 'center',
                                        minHeight: '40px'
                                    }}
                                    disabled={isSaving}
                                >
                                    {__('Remove Image', 'super-swank-featured-image')}
                                </Button>
                            )}
                        </div>
                    )}
                />
                {defaultImage > 0 && (
                    <div className="ssfi-crop-positions">
                        <h3>{__('Crop Positions', 'super-swank-featured-image')}</h3>
                        {Object.entries(platforms).map(([key, label]) => (
                            <CropPositionControl
                                key={key}
                                platform={label}
                                value={cropPositions[key] || { x: 'center', y: 'center' }}
                                onChange={(position) => updateCropPosition(key, position)}
                            />
                        ))}
                    </div>
                )}
                {isSaving && (
                    <div style={{ textAlign: 'center', padding: '1em' }}>
                        {__('Saving changes...', 'super-swank-featured-image')}
                    </div>
                )}
            </div>
        </PanelBody>
    );
};

registerPlugin('ssfi-default-image', {
    render: () => (
        <PluginSidebar
            name="ssfi-default-image-sidebar"
            title={__('Default Featured Image', 'super-swank-featured-image')}
            icon="format-image"
        >
            <SuperSwankFeaturedImagePanel />
        </PluginSidebar>
    ),
    icon: 'format-image'
}); 