import React, { useEffect, useRef, useState } from 'react';
import { View, Text, FlatList, Dimensions, TouchableOpacity, SafeAreaView, StyleSheet, Platform, StatusBar, useWindowDimensions } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import Slide1 from '../../../assets/onboarding-1.svg';
import Slide2 from '../../../assets/onboarding-2.svg';
import Slide3 from '../../../assets/onboarding-3.svg';

// Base width used for deriving responsive values; actual width comes from useWindowDimensions
const BASE_WIDTH = 375; // iPhone 12 baseline

const slides = [
  {
    key: 'slide1',
    titleTop: 'Send from Cameroon to ',
    titleEm: 'Nigeria',
    titleBottom: ' fast',
    svg: Slide1,
  },
  {
    key: 'slide2',
    titleTop: 'Best ',
    titleEm: 'rates',
    titleBottom: ' low fees',
    svg: Slide2,
  },
  {
    key: 'slide3',
    titleTop: 'Instant ',
    titleEm: 'delivery',
    titleBottom: ' bank & wallet',
    svg: Slide3,
  },
];

export default function Onboarding() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [index, setIndex] = useState(0);
  const ref = useRef<FlatList<any>>(null);

  console.log('Onboarding StyleSheet layout v2 loaded');

  // Responsive metrics
  const { width, height } = useWindowDimensions();
  const scale = Math.max(0.85, Math.min(1.25, width / BASE_WIDTH));
  const pagePaddingH = Math.round(Math.max(24, Math.min(44, width * 0.10))); // 10% width, 24..44
  const headingFont = Math.round(Math.max(26, Math.min(34, 30 * scale)));
  const headingLine = Math.round(headingFont * 1.33);
  const illoWidth = Math.round(Math.min(width * 0.8, 360));
  const illoHeight = Math.round(Math.min(width * 0.55, 260));
  const dotsBottom = Math.round(Math.max(24, Math.min(48, height * 0.04))); // 4% height
  const ctaBottom = Math.round(Math.max(56, Math.min(96, height * 0.08))); // 8% height
  const ctaGap = Math.round(Math.max(16, Math.min(28, 20 * scale)));
  // Slightly smaller horizontal padding for CTA block so buttons are wider than the page content
  const ctaPaddingH = Math.round(Math.max(16, Math.min(28, width * 0.06)));

  const onScroll = (e: any) => {
    const i = Math.round(e.nativeEvent.contentOffset.x / width);
    setIndex(i);
  };

  const next = () => {
    if (index < slides.length - 1) ref.current?.scrollToIndex({ index: index + 1, animated: true });
  };

  // Auto-advance slides every 4 seconds, rely on scroll to update index for smoother UI
  useEffect(() => {
    const id = setInterval(() => {
      const nextIndex = index + 1 < slides.length ? index + 1 : 0;
      ref.current?.scrollToIndex({ index: nextIndex, animated: true });
    }, 4000);
    return () => clearInterval(id);
  }, [index]);

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 0 }]}>
      <FlatList
        ref={ref}
        data={slides}
        keyExtractor={(item) => item.key}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onScroll={onScroll}
        renderItem={({ item }) => {
          const Svg = item.svg;
          return (
            <View style={[{ width }, styles.page, { paddingHorizontal: pagePaddingH }]}>
              {/* Heading */}
              <View style={styles.headingWrap}>
                <Text style={[styles.headingText, { fontSize: headingFont, lineHeight: headingLine }]}>
                  {item.titleTop}
                  <Text style={styles.headingEm}>{item.titleEm}</Text>
                  {item.titleBottom}
                </Text>
              </View>

              {/* Illustration */}
              <View style={styles.illustrationWrap}>
                <Svg
                  width={illoWidth}
                  height={illoHeight}
                  preserveAspectRatio="xMidYMid meet"
                />
              </View>
            </View>
          );
        }}
      />
      {/* Static dots */}
      <View style={[styles.dotsWrap, { marginBottom: dotsBottom }]}>
        <View style={styles.dotsRow}>
          {slides.map((_, i) => (
            <View key={i} style={i === index ? styles.dotActive : styles.dot} />
          ))}
        </View>
      </View>

      {/* Static CTAs */}
      <View style={[styles.ctaWrap, { marginBottom: ctaBottom, paddingHorizontal: ctaPaddingH }]}>
        <TouchableOpacity activeOpacity={0.9} style={styles.primaryBtn} onPress={() => navigation.navigate('Register')}>
          <Text style={styles.primaryBtnText}>Create a new account</Text>
        </TouchableOpacity>
        <TouchableOpacity activeOpacity={0.9} style={[styles.outlineBtn, { marginTop: ctaGap }]} onPress={() => navigation.navigate('Login')}>
          <Text style={styles.outlineBtnText}>Login</Text>
        </TouchableOpacity>
        <TouchableOpacity activeOpacity={0.8} style={{ alignItems: 'center', marginTop: 12 }} onPress={() => navigation.navigate('PasswordForgot')}>
          <Text style={{ color: '#1543A6', fontWeight: '500' }}>Forgot Password ?</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F4F6FE',
  },
  page: {
    flex: 1,
    paddingTop: 16,
  },
  headingWrap: {
    alignItems: 'center',
  },
  headingText: {
    textAlign: 'center',
    color: '#0B0F1A',
  },
  headingEm: {
    color: '#1543A6',
    fontWeight: '600',
  },
  illustrationWrap: {
    flexGrow: 1,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 12,
  },
  dotsWrap: {
    alignItems: 'center',
    marginBottom: 36,
  },
  dotsRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  dot: {
    width: 10,
    height: 10,
    borderRadius: 10,
    backgroundColor: '#E8ECF8',
    marginHorizontal: 4,
  },
  dotActive: {
    width: 10,
    height: 10,
    borderRadius: 10,
    backgroundColor: '#1543A6',
    marginHorizontal: 4,
  },
  ctaWrap: {
    marginBottom: 72,
  },
  primaryBtn: {
    backgroundColor: '#1543A6',
    borderRadius: 999,
    paddingVertical: 16,
    alignItems: 'center',
    width: '100%',
  },
  primaryBtnText: {
    color: '#FFFFFF',
    fontWeight: '500',
  },
  outlineBtn: {
    borderColor: '#1543A6',
    borderWidth: 2,
    borderRadius: 999,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 20,
    width: '100%',
  },
  outlineBtnText: {
    color: '#1543A6',
    fontWeight: '500',
  },
});
