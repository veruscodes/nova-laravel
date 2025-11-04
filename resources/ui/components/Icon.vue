<script setup lang="ts">
import { computed } from 'vue'
import type { IconType } from './types'
import * as outlineIcons from '@heroicons/vue/24/outline'
import * as solidIcons from '@heroicons/vue/24/solid'
import * as miniIcons from '@heroicons/vue/20/solid'
import * as microIcons from '@heroicons/vue/16/solid'
import camelCase from 'lodash/camelCase'
import startCase from 'lodash/startCase'

type IconProps = {
  name?: string
  type?: IconType
}

const props = withDefaults(defineProps<IconProps>(), {
  name: 'ellipsis-horizontal',
  type: 'outline',
})

const iconTypes = {
  solid: solidIcons,
  outline: outlineIcons,
  mini: miniIcons,
  micro: microIcons,
}

const aliases = {
  Adjustments: 'AdjustmentsVertical',
  Annotation: 'ChatBubbleBottomCenterText',
  Archive: 'ArchiveBox',
  ArrowCircleDown: 'ArrowDownCircle',
  ArrowCircleLeft: 'ArrowLeftCircle',
  ArrowCircleRight: 'ArrowRightCircle',
  ArrowCircleUp: 'ArrowUpCircle',
  ArrowNarrowDown: 'ArrowLongDown',
  ArrowNarrowLeft: 'ArrowLongLeft',
  ArrowNarrowRight: 'ArrowLongRight',
  ArrowNarrowUp: 'ArrowLongUp',
  ArrowsExpand: 'ArrowsPointingOut',
  ArrowSmDown: 'ArrowSmallDown',
  ArrowSmLeft: 'ArrowSmallLeft',
  ArrowSmRight: 'ArrowSmallRight',
  ArrowSmUp: 'ArrowSmallUp',
  BadgeCheck: 'CheckBadge',
  Ban: 'NoSymbol',
  BookmarkAlt: 'BookmarkSquare',
  Cash: 'Banknotes',
  ChartSquareBar: 'ChartBarSquare',
  ChatAlt2: 'ChatBubbleLeftRight',
  ChatAlt: 'ChatBubbleLeftEllipsis',
  Chat: 'ChatBubbleOvalLeftEllipsis',
  Chip: 'CpuChip',
  ClipboardCheck: 'ClipboardDocumentCheck',
  ClipboardCopy: 'ClipboardDocument',
  ClipboardList: 'ClipboardDocumentList',
  CloudDownload: 'CloudArrowDown',
  CloudUpload: 'CloudArrowUp',
  Code: 'CodeBracket',
  Collection: 'RectangleStack',
  ColorSwatch: 'Swatch',
  CursorClick: 'CursorArrowRays',
  Database: 'CircleStack',
  DesktopComputer: 'ComputerDesktop',
  DeviceMobile: 'DevicePhoneMobile',
  DocumentAdd: 'DocumentPlus',
  DocumentDownload: 'DocumentArrowDown',
  DocumentRemove: 'DocumentMinus',
  DocumentReport: 'DocumentChartBar',
  DocumentSearch: 'DocumentMagnifyingGlass',
  DotsCircleHorizontal: 'EllipsisHorizontalCircle',
  DotsHorizontal: 'EllipsisHorizontal',
  DotsVertical: 'EllipsisVertical',
  Download: 'ArrowDownTray',
  Duplicate: 'Square2Stack',
  EmojiHappy: 'FaceSmile',
  EmojiSad: 'FaceFrown',
  Exclamation: 'ExclamationTriangle',
  ExternalLink: 'ArrowTopRightOnSquare',
  EyeOff: 'EyeSlash',
  FastForward: 'Forward',
  Filter: 'Funnel',
  FolderAdd: 'FolderPlus',
  FolderDownload: 'FolderArrowDown',
  FolderRemove: 'FolderMinus',
  Globe: 'GlobeAmericas',
  Hand: 'HandRaised',
  InboxIn: 'InboxArrowDown',
  Library: 'BuildingLibrary',
  LightningBolt: 'Bolt',
  LocationMarker: 'MapPin',
  Login: 'ArrowLeftOnRectangle',
  Logout: 'ArrowRightOnRectangle',
  Mail: 'Envelope',
  MailOpen: 'EnvelopeOpen',
  MenuAlt1: 'Bars3CenterLeft',
  MenuAlt2: 'Bars3BottomLeft',
  MenuAlt3: 'Bars3BottomRight',
  MenuAlt4: 'Bars2',
  Menu: 'Bars3',
  MinusSm: 'MinusSmall',
  MusicNote: 'MusicalNote',
  OfficeBuilding: 'BuildingOffice',
  PencilAlt: 'PencilSquare',
  PhoneIncoming: 'PhoneArrowDownLeft',
  PhoneMissedCall: 'PhoneXMark',
  PhoneOutgoing: 'PhoneArrowUpRight',
  Photograph: 'Photo',
  PlusSm: 'PlusSmall',
  Puzzle: 'PuzzlePiece',
  Qrcode: 'QrCode',
  ReceiptTax: 'ReceiptPercent',
  Refresh: 'ArrowPath',
  Reply: 'ArrowUturnLeft',
  Rewind: 'Backward',
  SaveAs: 'ArrowDownOnSquareStack',
  Save: 'ArrowDownOnSquare',
  SearchCircle: 'MagnifyingGlassCircle',
  Search: 'MagnifyingGlass',
  Selector: 'ChevronUpDown',
  SortAscending: 'BarsArrowUp',
  SortDescending: 'BarsArrowDown',
  Speakerphone: 'Megaphone',
  StatusOffline: 'SignalSlash',
  StatusOnline: 'Signal',
  Support: 'Lifebuoy',
  SwitchHorizontal: 'ArrowsRightLeft',
  SwitchVertical: 'ArrowsUpDown',
  Table: 'TableCells',
  Template: 'RectangleGroup',
  Terminal: 'CommandLine',
  ThumbDown: 'HandThumbDown',
  ThumbUp: 'HandThumbUp',
  Translate: 'Language',
  TrendingDown: 'ArrowTrendingDown',
  TrendingUp: 'ArrowTrendingUp',
  Upload: 'ArrowUpTray',
  UserAdd: 'UserPlus',
  UserRemove: 'UserMinus',
  ViewBoards: 'ViewColumns',
  ViewGridAdd: 'SquaresPlus',
  ViewGrid: 'Squares2X2',
  ViewList: 'Bars4',
  VolumeOff: 'SpeakerXMark',
  VolumeUp: 'SpeakerWave',
  X: 'XMark',
  ZoomIn: 'MagnifyingGlassPlus',
  ZoomOut: 'MagnifyingGlassMinus',
}

const component = computed(function () {
  if (!checkType(props.type)) {
    throw new Error(`Invalid icon type: ${props.type}`)
  }

  const name = startCase(camelCase(props.name)).replace(/ /g, '')

  if (aliases[name]) {
    return iconTypes[props.type][aliases[name] + 'Icon']
  }

  return iconTypes[props.type][name + 'Icon']
})

const classes = computed(() => {
  if (props.type === 'mini') {
    return 'w-5 h-5'
  }

  if (props.type === 'micro') {
    return 'w-4 h-4'
  }

  return 'w-6 h-6'
})

function checkType(type) {
  return Object.keys(iconTypes).includes(type)
}
</script>

<template>
  <component :is="component" :class="classes" />
</template>
